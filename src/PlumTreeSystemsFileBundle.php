<?php

namespace PlumTreeSystems\FileBundle;

use PlumTreeSystems\FileBundle\Security\SecurityProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PlumTreeSystemsFileBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new SecurityProviderPass());
    }

    static public $AVAILABLE_PROVIDERS = [
        'local'
    ];
}
