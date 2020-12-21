<?php

namespace PlumTreeSystems\FileBundle\DependencyInjection;

use PlumTreeSystems\FileBundle\PlumTreeSystemsFileBundle;
use PlumTreeSystems\FileBundle\Service\GaufretteFileManager;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
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

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $provider = $config['provider'];

        if (!in_array($provider, PlumTreeSystemsFileBundle::$AVAILABLE_PROVIDERS)) {
            throw new InvalidConfigurationException(
                "PTSFileBundle bad configuration, configured provider does not exist: ".$provider
            );
        }
        $providerConfig = $config['provider_configs'][$provider];
        $fileClass = $config['file_class'];
        $prefixPath = $config['prefix_path'];
        $replace = isset($config['replace_file'])? $config['replace_file'] : false;
        $container->setParameter('pts_file_provider', $provider);
        $container->setParameter('pts_file_provider_settings', $providerConfig);
        $container->setParameter('pts_file_extended_entity', $fileClass);
        $container->setParameter('pts_file_replace', $replace);
        $container->setParameter('pts_file_prefix_path', $prefixPath);

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
