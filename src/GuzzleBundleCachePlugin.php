<?php

namespace Gregurco\Bundle\GuzzleBundleCachePlugin;

use EightPoints\Bundle\GuzzleBundle\EightPointsGuzzleBundlePlugin;
use Gregurco\Bundle\GuzzleBundleCachePlugin\DependencyInjection\GuzzleCacheExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GuzzleBundleCachePlugin extends Bundle implements EightPointsGuzzleBundlePlugin
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterListenersPass(
            'guzzle_cache.event_dispatcher',
            'guzzle_cache.event_listener',
            'guzzle_cache.event_subscriber'
        ));
    }

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $extension = new GuzzleCacheExtension();
        $extension->load($configs, $container);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     * @param string           $clientName
     * @param Definition       $handler
     */
    public function loadForClient(array $config, ContainerBuilder $container, string $clientName, Definition $handler)
    {
        if ($config['enabled']) {
            $cacheMiddlewareDefinitionName = sprintf('guzzle_bundle_cache_plugin.middleware.%s', $clientName);
            $cacheMiddlewareDefinition     = new Definition('%guzzle_bundle_cache_plugin.middleware.class%');

            if ($config['strategy']) {
                $cacheMiddlewareDefinition->addArgument(new Reference($config['strategy']));
            }

            $container->setDefinition($cacheMiddlewareDefinitionName, $cacheMiddlewareDefinition);

            $cacheMiddlewareExpression = new Expression(sprintf('service("%s")', $cacheMiddlewareDefinitionName));

            $handler->addMethodCall('push', [$cacheMiddlewareExpression, 'cache']);
        }
    }

    /**
     * @param ArrayNodeDefinition $pluginNode
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->canBeEnabled()
            ->children()
                ->scalarNode('strategy')->defaultNull()->end()
            ->end()
        ;
    }

    /**
     * @return string
     */
    public function getPluginName(): string
    {
        return 'cache';
    }
}
