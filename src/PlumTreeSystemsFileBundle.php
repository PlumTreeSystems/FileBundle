<?php

namespace PlumTreeSystems\FileBundle;

use PlumTreeSystems\FileBundle\Security\SecurityProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PlumTreeSystemsFileBundle extends Bundle
{
    const LOCAL_PROVIDER = 'local';
    const AWS_S3_PROVIDER = 'aws_s3';
    const GOOGLE_STORAGE_CLOUD_PROVIDER = 'google_cloud_storage';

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new SecurityProviderPass());
    }

    public static $AVAILABLE_PROVIDERS = [
        self::LOCAL_PROVIDER,
        self::AWS_S3_PROVIDER,
        self::GOOGLE_STORAGE_CLOUD_PROVIDER
    ];
}
