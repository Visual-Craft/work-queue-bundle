<?php

namespace VisualCraft\Bundle\WorkQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterWorkersCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $workersMap = $container->getParameterBag()->get('visual_craft_work_queue.workers_map');

        foreach ($workersMap as $queueId => $workerId) {
            $container->findDefinition("visual_craft_work_queue.processor.{$queueId}")
                ->setArgument('$worker', new Reference($workerId))
            ;
        }
    }
}
