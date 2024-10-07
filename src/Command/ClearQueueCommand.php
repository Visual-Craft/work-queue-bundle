<?php

namespace VisualCraft\Bundle\WorkQueueBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use VisualCraft\WorkQueue\QueueManager;

#[AsCommand(name: 'vc:work-queue:clear')]
class ClearQueueCommand extends Command
{
    private ServiceLocator $managersLocator;

    private array $queues;

    public function __construct(ServiceLocator $managersLocator, array $queues)
    {
        parent::__construct(self::$defaultName);
        $this->managersLocator = $managersLocator;
        $this->queues = $queues;
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->addArgument('queues', InputArgument::IS_ARRAY)
            ->addOption('all', 'a')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queues = array_unique($input->getArgument('queues'));

        if ($queues) {
            $missingQueues = [];

            foreach ($queues as $queue) {
                if (!$this->managersLocator->has($queue)) {
                    $missingQueues[] = $queue;
                }
            }

            if ($missingQueues) {
                throw new \InvalidArgumentException(sprintf("Work queues ['%s'] are not registered", implode("', '", $missingQueues)));
            }
        } elseif (!$input->getOption('all')) {
            throw new \InvalidArgumentException("You should provide queue name or use '--all' option to clear all queues.");
        } else {
            $queues = $this->queues;
        }

        foreach ($queues as $queue) {
            /** @var QueueManager $manager */
            $manager = $this->managersLocator->get($queue);
            $manager->clear();
        }

        return 0;
    }
}
