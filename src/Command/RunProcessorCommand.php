<?php

namespace VisualCraft\Bundle\WorkQueueBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class RunProcessorCommand extends Command
{
    protected static $defaultName = 'vc:work-queue:run';

    private ServiceLocator $managersLocator;

    public function __construct(ServiceLocator $managersLocator)
    {
        parent::__construct(self::$defaultName);
        $this->managersLocator = $managersLocator;
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->addArgument('queue', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getArgument('queue');

        if (!$this->managersLocator->has($queue)) {
            throw new \InvalidArgumentException(sprintf("Processor with id '%s' does not exist.", $queue));
        }

        $processor = $this->managersLocator->get($queue);
        while($processor->process()) {}

        return 0;
    }
}
