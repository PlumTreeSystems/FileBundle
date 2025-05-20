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
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('plum_tree_systems_file');
        $rootNode = $treeBuilder->getRootNode();

        /** @phpstan-ignore method.notFound */
        $rootNode
            ->children()
                ->arrayNode('path_map')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('provider')->isRequired()->end()
                        ->end()
                    ->end()
                ->end() // path_map
                ->scalarNode('default_provider')->end()
                ->arrayNode('generic_providers')
                    ->children()
                        ->arrayNode('s3')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('bucket')->isRequired()->end()
                                    ->scalarNode('region')->isRequired()->end()
                                    ->scalarNode('key')->isRequired()->end()
                                    ->scalarNode('secret')->isRequired()->end()
                                    ->scalarNode('prefix')->defaultValue('')->end()
                                ->end()
                            ->end()
                        ->end() // s3
                        ->arrayNode('local')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('dir')->isRequired()->end()
                                    ->scalarNode('dir_url')->end()
                                ->end()
                            ->end()
                        ->end() // local
                    ->end()
                ->end() // generic_provicers
                ->scalarNode('file_class')->isRequired()->end()
                ->booleanNode('replace_file')->end()
            ->end();

        return $treeBuilder;
    }
}
