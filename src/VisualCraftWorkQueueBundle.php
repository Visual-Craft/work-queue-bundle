<?php

namespace VisualCraft\Bundle\WorkQueueBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use VisualCraft\Bundle\WorkQueueBundle\DependencyInjection\Compiler\RegisterWorkersCompilerPass;

class VisualCraftWorkQueueBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterWorkersCompilerPass());
    }
}
