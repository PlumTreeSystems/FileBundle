<?php

namespace PlumTreeSystems\FileBundle\DependencyInjection;

use PlumTreeSystems\FileBundle\Provider\LocalFileProvider;
use PlumTreeSystems\FileBundle\Provider\S3FileProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class PlumTreeSystemsFileExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $fileClass = $config['file_class'];
        $replace = isset($config['replace_file']) ? $config['replace_file'] : false;
        $container->setParameter('pts_file_extended_entity', $fileClass);
        $container->setParameter('pts_file_replace', $replace);

        // Universal manager
        $pathMappings = [];
        foreach ($config['path_map'] as $path => $pathConfig) {
            $pathMappings[$path] = $pathConfig['provider'];
        }
        $container->setParameter('pts_file_path_map', $pathMappings);
        $container->setParameter('pts_file_default_provider', $config['default_provider'] ?? '');

        foreach ($config['generic_providers']['s3'] as $providerName => $s3Provider) {
            $def = new Definition(S3FileProvider::class, [[
                'credentials' => [
                    'key' => $s3Provider['key'],
                    'secret' => $s3Provider['secret']
                ],
                'region' => $s3Provider['region'],
                'bucket' => $s3Provider['bucket'],
                'prefix' => $s3Provider['prefix']
            ]]);
            $def->addTag('pts.file.provider');
            $def->setPublic(true);
            $container->setDefinition($providerName, $def);
        }

        foreach ($config['generic_providers']['local'] as $providerName => $localProvider) {
            $def = new Definition(LocalFileProvider::class, [
                $localProvider['dir'],
                $localProvider['dir_url'] ?? ''
            ]);
            $def->addTag('pts.file.provider');
            $def->setPublic(true);
            $container->setDefinition($providerName, $def);
        }

        $this->registerFormTheme($container);
    }

    private function registerFormTheme(ContainerBuilder $container): void
    {
        /** @var array<string> $resources */
        $resources = $container->hasParameter('twig.form.resources')
            ? $container->getParameter('twig.form.resources')
            : [];

        array_unshift($resources, '@PlumTreeSystemsFile/Form/fields.html.twig');
        $container->setParameter('twig.form.resources', $resources);
    }
}
