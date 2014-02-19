<?php

namespace Guerriat\MetricsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('guerriat_metrics');

        // Initialize
        $rootNode
            ->beforeNormalization()
            ->always(
                function ($config) {
                    return array(
                        'servers' => isset($config['servers']) ? $config['servers'] : null,
                        'clients' => isset($config['clients']) ? $config['clients'] : null,
                    );
                }
            )
            ->end();

        // Configure the "servers" section
        $this->addServersSection($rootNode);

        // Configure the "clients" section
        $this->addClientsSection($rootNode);

        return $treeBuilder;
    }

    private function addServersSection($rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('servers')
                    ->beforeNormalization()
                    ->ifNull()
                        ->then(function() { return array('default' => array()); })
                    ->end()
                    ->useAttributeAsKey('alias', false)
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                            ->scalarNode('port')->defaultValue(8125)->end()
                            ->scalarNode("class")->defaultValue('Guerriat\MetricsBundle\Sender\StatsdSender')->end()
                            ->scalarNode("udp_max_size")->defaultValue(512)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addClientsSection($rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('clients')
                    ->beforeNormalization()
                    ->ifNull()
                        ->then(function() { return array('default' => array()); })
                    ->end()
                    ->useAttributeAsKey('alias', false)
                    ->prototype('array')
                        ->children()
                            ->arrayNode('servers')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('events')
                                ->useAttributeAsKey('eventName')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('increment')->end()
                                        ->scalarNode('decrement')->end()
                                        ->arrayNode('timer')
                                            ->beforeNormalization()
                                            ->ifString()
                                                ->then(function($value) { return array('key' => $value); })
                                            ->end()
                                            ->children()
                                                ->scalarNode('key')->isRequired()->end()
                                                ->scalarNode('method')->defaultValue('getDuration')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('counter')
                                            ->beforeNormalization()
                                            ->ifString()
                                                ->then(function($value) { return array('key' => $value); })
                                            ->end()
                                            ->children()
                                                ->scalarNode('key')->isRequired()->end()
                                                ->scalarNode('method')->defaultValue('getValue')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('gauge')
                                            ->beforeNormalization()
                                            ->ifString()
                                                ->then(function($value) { return array('key' => $value); })
                                            ->end()
                                            ->children()
                                                ->scalarNode('key')->isRequired()->end()
                                                ->scalarNode('method')->defaultValue('getValue')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('set')
                                            ->beforeNormalization()
                                            ->ifString()
                                                ->then(function($value) { return array('key' => $value); })
                                            ->end()
                                            ->children()
                                                ->scalarNode('key')->isRequired()->end()
                                                ->scalarNode('method')->defaultValue('getValue')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('collectors')
                                ->useAttributeAsKey('collectorName')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('monolog')
                                ->children()
                                    ->scalarNode('enable')->defaultValue(false)->end()
                                    ->scalarNode('prefix')->defaultValue("log")->end()
                                    ->scalarNode('level')->defaultValue("warning")->end()
                                    ->arrayNode('formatter')
                                        ->children()
                                            ->scalarNode("class")->defaultValue('Guerriat\MetricsBundle\Monolog\MetricFormatter')->end()
                                            ->scalarNode("format")->defaultValue(null)->end()
                                            ->booleanNode("context_logging")->defaultValue(false)->end()
                                            ->booleanNode("extra_logging")->defaultValue(false)->end()
                                            ->scalarNode("words")->defaultValue(2)->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

}
