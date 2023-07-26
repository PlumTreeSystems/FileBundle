<?php

namespace PlumTreeSystems\FileBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use PlumTreeSystems\FileBundle\Service\UniversalFileManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigurationTest extends KernelTestCase
{
    private ContainerInterface $container;

    public function setUp(): void 
    {
        self::bootKernel(['debug' => false]);
        $this->container = static::getContainer();
    }

    #[Test]
    public function shouldHavePathMap() {
        $map = $this->container->getParameter('pts_file_path_map');
        $this->assertEquals([
            'bucket1/files' => 's3_files_1',
            'local/location' => 'local_1',
        ], $map);
    }

    #[Test]
    public function shouldDefaultProvider() {
        $provider = $this->container->getParameter('pts_file_default_provider');
        $this->assertEquals('s3_files_1', $provider);
    }

    #[Test]
    public function shouldHaveGenericProviders() {
        $this->assertTrue($this->container->has('s3_files_1'));
        $this->assertTrue($this->container->has('s3_files_2'));
        $this->assertTrue($this->container->has('local_1'));
    }

    #[Test]
    public function shouldHaveUniversalProvider() {
        $this->assertTrue($this->container->has(UniversalFileManager::class));
        $fm = $this->container->get(UniversalFileManager::class);
        $providers = $fm->getSupportedProviders();
        $this->assertContains('s3_files_1', $providers);
        $this->assertContains('local_1', $providers);
    }

}
