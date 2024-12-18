<?php

namespace VisualCraft\Bundle\WorkQueueBundle\DependencyInjection;

use Pheanstalk\Pheanstalk;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use VisualCraft\WorkQueue\JobAdder;
use VisualCraft\WorkQueue\Logger;
use VisualCraft\WorkQueue\QueueManager;
use VisualCraft\WorkQueue\QueueProcessor;
use VisualCraft\WorkQueue\QueueProcessor\QueueProcessorLimits;
use VisualCraft\WorkQueue\QueueProcessor\RetryDelayProvider;
use VisualCraft\WorkQueue\QueueProcessor\SchemeRetryDelayProvider;

class VisualCraftWorkQueueExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $this->registerConnections($container, $config['connections']);
        $this->registerQueues($container, $config['queues']);
    }

    private function registerConnections(ContainerBuilder $container, array $connectionsConfig)
    {
        foreach ($connectionsConfig as $connectionId => $connectionConfig) {
            $connectTimeout = $connectionConfig['connectTimeout'] === null
                ? $connectionConfig['connectTimeout']
                : max((int) $connectionConfig['connectTimeout'], 0)
            ;

            $connectionDefinition = new Definition(
                Pheanstalk::class,
                [$connectionConfig['host'], $connectionConfig['port'], $connectTimeout]
            );
            $connectionDefinition->setFactory([Pheanstalk::class, 'create']);
            $container->setDefinition(
                "visual_craft_work_queue.connection.{$connectionId}",
                $connectionDefinition
            );
        }
    }

    private function registerQueues(ContainerBuilder $container, array $queuesConfig)
    {
        $workersMap = [];
        $servicesPrefix = "visual_craft_work_queue";

        foreach ($queuesConfig as $queueId => $queueConfig) {
            $connection = new Reference("{$servicesPrefix}.connection.{$queueConfig['connection']}");
            $manager = new Definition(QueueManager::class);
            $manager
                ->setArgument('$connection', $connection)
                ->setArgument('$queueName', $queueId)
                ->setArgument('$logger', new Reference(Logger::class))
                ->setArgument('$ttr', $queueConfig['ttr'])
                ->addTag("{$servicesPrefix}.manager", ['key' => $queueId])
            ;

            $managerServiceId = "{$servicesPrefix}.manager.{$queueId}";
            $container->setDefinition($managerServiceId, $manager);

            $adder = new Definition(JobAdder::class);
            $adder
                ->setArgument('$queueManager', new Reference($managerServiceId))
            ;
            $container->setDefinition("{$servicesPrefix}.adder.{$queueId}", $adder);

            $processor = new Definition(QueueProcessor::class);
            $processor
                ->setArgument('$queueManager', new Reference($managerServiceId))
                ->addTag("{$servicesPrefix}.processor", ['key' => $queueId])
            ;

            $retryDelayProvider = null;

            if (($retrySchemeConfig = $queueConfig['retry_scheme'] ?? null) !== null) {
                $retryDelayProvider = new Definition(SchemeRetryDelayProvider::class);
                $retryDelayProvider->setArguments([$retrySchemeConfig]);

            } elseif (($retryConfig = $queueConfig['retry'] ?? null) !== null) {
                $retryDelayProvider = new Definition(RetryDelayProvider::class);
                $retryDelayProvider->setArguments([
                    $retryConfig['initial_delay'],
                    $retryConfig['max_attempts'],
                    $retryConfig['backoff'],
                ]);
            }

            if ($retryDelayProvider) {
                $retryDelayProviderServiceId = "{$servicesPrefix}.retry_delay_provider.{$queueId}";
                $container->setDefinition($retryDelayProviderServiceId, $retryDelayProvider);
                $processor->setArgument('$retryDelayProvider', new Reference($retryDelayProviderServiceId));
            }

            if (($limitsConfig = $queueConfig['limits'] ?? null) !== null) {
                $limits = new Definition(QueueProcessorLimits::class);
                $limits->setArguments([$limitsConfig['time'], $limitsConfig['jobs']]);
                $LimitsServiceId = "{$servicesPrefix}.limits.{$queueId}";
                $container->setDefinition($LimitsServiceId, $limits);
                $processor->setArgument('$limits', new Reference($LimitsServiceId));
            }

            $container->setDefinition("{$servicesPrefix}.processor.{$queueId}", $processor);
            $workersMap[$queueId] = $queueConfig['worker'];
        }

        $container->setParameter('visual_craft_work_queue.queues', array_keys($workersMap));
        $container->setParameter('visual_craft_work_queue.workers_map', $workersMap);
    }
}
