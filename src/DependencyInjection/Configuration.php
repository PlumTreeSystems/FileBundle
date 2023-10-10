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
        $treeBuilder = new TreeBuilder('plum_tree_systems_file');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('path_map')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('provider')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
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
                        ->end()
                        ->arrayNode('local')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('dir')->isRequired()->end()
                                    ->scalarNode('dir_url')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('provider')->end()
                ->arrayNode('provider_configs')
                    ->children()
                        ->arrayNode('local')
                            ->children()
                                ->scalarNode('directory')->end()
                                ->scalarNode('web_root')->end()
                            ->end()
                        ->end()
                        ->arrayNode('aws_s3')
                            ->children()
                                ->scalarNode('key')->end()
                                ->scalarNode('secret')->end()
                                ->scalarNode('bucket_name')->end()
                                ->scalarNode('region')->end()
                                ->scalarNode('version')->end()

                            ->end()
                        ->end()
                        ->arrayNode('google_cloud_storage')
                            ->children()
                                ->scalarNode('google_bucket')->end()
                                ->scalarNode('auth_config_json')->end()

                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('file_class')->isRequired()->end()
                ->scalarNode('prefix_path')->defaultValue('')->end()
                ->booleanNode('replace_file')->end()

            ->end();
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
