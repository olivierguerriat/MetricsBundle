<?php

namespace Guerriat\MetricsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;


/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GuerriatMetricsExtension extends Extension
{

    protected $serviceBaseId = 'guerriat_metrics';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Load parameters & services
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        // Load Sender services
        $this->loadSenders($container, $config['servers']);

        // Run through clients and load their services
        foreach ($config['clients'] as $alias => $clientConfig) {
            $this->loadClient($container, $alias, $clientConfig, $config['servers']);
        }

    }

    /**
     * Load Transport services
     * @param $container
     * @param $servers
     */
    protected function loadSenders(ContainerBuilder $container, array $servers)
    {
        foreach ($servers as $alias => $config) {
            $definition = new Definition($config['class']);
            $definition->addArgument($config);
            $definition->addTag(
                'kernel.event_listener',
                array('event' => 'kernel.terminate', 'method' => 'onKernelTerminate', 'priority' => -100)
            );
            $container->setDefinition($this->serviceBaseId . '.sender.' . $alias, $definition);
        }
    }

    /**
     * Load Client services (and related ones)
     * @param ContainerBuilder $container
     * @param $alias
     * @param array $config
     * @param array $servers
     * @throws \Exception
     */
    protected function loadClient(ContainerBuilder $container, $alias, array $config, array $servers)
    {

        // Server selection
        $clientServers = array();
        // If specified or by default, use all configured servers
        if (empty($config['servers']) || $config['servers'][0] == 'all') {
            $clientServers = $servers;
        } else {
            foreach ($config['servers'] as $serverAlias) {
                if (!isset($servers[$serverAlias])) {
                    throw new \Exception($this->serviceBaseId . ' client ' . $alias . ' used undefined server "' . $serverAlias . '".');
                } else {
                    $clientServers[] = $servers[$serverAlias];
                }
            }
        }
        $clientSenders = array();
        foreach ($clientServers as $serverAlias => $serverConfig) {
            $clientSenders[] = new Reference($this->serviceBaseId . '.sender.' . $serverAlias);
        }

        // Client service setup
        $serviceId = ($alias == 'default') ? $this->serviceBaseId : $this->serviceBaseId . '.' . $alias;
        $definition = new Definition('%' . $this->serviceBaseId . '.client.class%');
        $definition->addArgument($clientSenders);
        
        // Setting Metric classes
        $metrics = array('counter', 'set', 'gauge', 'timer');
        foreach ($metrics as $metric) {
            $definition->addMethodCall('setMetricClass', array($metric, '%' . $this->serviceBaseId . '.'.$metric.'.class%'));
        }

        // Add events to be listened to
        if (!empty($config['events'])) {
            foreach ($config['events'] as $eventName => $eventConfig) {
                $definition->addTag(
                    'kernel.event_listener',
                    array(
                        'event' => $eventName,
                        'method' => 'handleEvent'
                    )
                );
                $definition->addMethodCall('addEventToListen', array($eventName, $eventConfig));
            }
        }

        $container->setDefinition($serviceId, $definition);

        // Collector services setup
        if (!empty($config['collectors'])) {
            $definition = new Definition('%' . $this->serviceBaseId . '.collector.manager.class%');
            $definition->addArgument(new Reference($serviceId));
            foreach ($config['collectors'] as $collectorService => $collectorKey) {
                $definition->addMethodCall('addCollector', array($collectorKey, new Reference($collectorService)));
            }
            $definition->addTag('kernel.event_subscriber');
            $container->setDefinition($serviceId . '.collector.manager', $definition);
        }

        // Monolog handler & formatter setup if enabled
        if (!empty($config['monolog']) && $config['monolog']['enable']) {
            $definition = new Definition($config['monolog']['formatter']['class']);
            $definition->addArgument($config['monolog']['formatter']['format']);
            $definition->addArgument($config['monolog']['formatter']['context_logging']);
            $definition->addArgument($config['monolog']['formatter']['extra_logging']);
            $definition->addArgument($config['monolog']['formatter']['words']);
            $container->setDefinition($serviceId . '.monolog.formatter', $definition);

            $definition = new Definition('%' . $this->serviceBaseId . '.monolog.handler.class%');
            $definition->addArgument(new Reference($serviceId));
            $definition->addArgument($config['monolog']['prefix']);
            $definition->addArgument($this->convertLevelToConstant($config['monolog']['level']));
            $definition->addMethodCall(
                'setFormatter',
                array(new Reference($serviceId . '.monolog.formatter'))
            );
            $container->setDefinition($serviceId . '.monolog.handler', $definition);
        }

    }

    /**
     * Return the log level
     * @param mixed $level
     * @return int level
     */
    private function convertLevelToConstant($level)
    {
        return is_int($level) ? $level : constant('Monolog\Logger::' . strtoupper($level));
    }

}
