<?php

namespace PlumTreeSystems\FileBundle;

use PlumTreeSystems\FileBundle\Security\SecurityProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PlumTreeSystemsFileBundle extends Bundle
{
    const LOCAL_PROVIDER = 'local';
    const AWS_S3_PROVIDER = 'aws_s3';

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new SecurityProviderPass());
    }

    static public $AVAILABLE_PROVIDERS = [
        self::LOCAL_PROVIDER,
        self::AWS_S3_PROVIDER
    ];
}
