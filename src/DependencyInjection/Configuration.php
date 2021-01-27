<?php

namespace VisualCraft\Bundle\WorkQueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('visual_craft_work_queue');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('connections')
                    ->useAttributeAsKey('key')
                    ->canBeUnset()
                    ->isRequired()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                            ->integerNode('port')->defaultValue(11300)->end()
                            ->integerNode('connectTimeout')->defaultValue(10)->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('queues')
                    ->isRequired()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('worker')->isRequired()->end()
                            ->integerNode('ttr')
                                ->defaultValue(3600)
                            ->end()
                            ->arrayNode('limits')
                                ->children()
                                    ->integerNode('time')
                                        ->isRequired()
                                    ->end()
                                    ->integerNode('jobs')
                                        ->isRequired()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('retry_scheme')
                                ->prototype('integer')->end()
                            ->end()
                            ->arrayNode('retry')
                                ->children()
                                    ->integerNode('initial_delay')
                                        ->isRequired()
                                    ->end()
                                    ->integerNode('max_attempts')
                                        ->isRequired()
                                    ->end()
                                    ->floatNode('backoff')
                                        ->defaultValue(1.0)
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) {
                                return ($v['retry_scheme'] ?? false) && ($v['retry'] ?? false);
                            })
                            ->thenInvalid('Only one value, retry_scheme or retry must be specified')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
