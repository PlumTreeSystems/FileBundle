<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-18
 * Time: 16:47
 */

namespace PlumTreeSystems\FileBundle\Security;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SecurityProviderPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('pts_file.security.manager')) {
            return;
        }

        $definition = $container->findDefinition('pts_file.security.manager');

        $taggedServices = $container->findTaggedServiceIds('pts_file.security_provider');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addSecurityProvider', [(new Reference($id))]);
        }
    }
}
