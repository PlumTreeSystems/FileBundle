<?php

namespace PlumTreeSystems\FileBundle;

use PlumTreeSystems\FileBundle\Security\SecurityProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PlumTreeSystemsFileBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new SecurityProviderPass());
    }
}
