<?php

namespace PlumTreeSystems\FileBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('plum_tree_systems_file');

        $rootNode
            ->children()
                ->scalarNode('provider')->end()
                ->arrayNode('provider_configs')
                    ->children()
                        ->arrayNode('local')
                            ->children()
                                ->scalarNode('directory')->end()
                                ->scalarNode('web_root')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('file_class')->isRequired()->end()
                ->booleanNode('replace_file')->end()
            ->end();
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
