<?php

namespace PlumTreeSystems\FileBundle\DependencyInjection;

use PlumTreeSystems\FileBundle\PlumTreeSystemsFileBundle;
use PlumTreeSystems\FileBundle\Provider\LocalFileProvider;
use PlumTreeSystems\FileBundle\Provider\S3FileProvider;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
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
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $deprProvider = $config['provider'] ?? null;

        if (null !== $deprProvider) {
            trigger_deprecation(
                'plumtreesystems/file-bundle',
                '2.3',
                'Gaufrette manager is now deprecated and will be removed in the next major update'
            );

            if (!in_array($deprProvider, PlumTreeSystemsFileBundle::$AVAILABLE_PROVIDERS)) {
                throw new InvalidConfigurationException(
                    "PTSFileBundle bad configuration, configured provider does not exist: " . $deprProvider
                );
            }
        }
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


        // Gaufrette manager config
        if (null !== $deprProvider) {
            $providerConfig = $config['provider_configs'][$deprProvider];
            $prefixPath = $config['prefix_path'];
            $container->setParameter('pts_file_provider', $deprProvider);
            $container->setParameter('pts_file_provider_settings', $providerConfig);
            $container->setParameter('pts_file_prefix_path', $prefixPath);
        }

        $this->registerFormTheme($container);
    }

    private function registerFormTheme(ContainerBuilder $container): void
    {
        $resources = $container->hasParameter('twig.form.resources') ?
            $container->getParameter('twig.form.resources') : [];

        array_unshift($resources, '@PlumTreeSystemsFile/Form/fields.html.twig');
        $container->setParameter('twig.form.resources', $resources);
    }
}
