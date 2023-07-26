<?php

namespace PlumTreeSystems\FileBundle\Tests\Provider;

use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Provider\LocalFileProvider;
use PlumTreeSystems\FileBundle\Tests\Functional\TestFile;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LocalFileProviderTest extends KernelTestCase
{
    private LocalFileProvider $provider;

    private string $dir;
    
    private $createdFiles = [];

    private function createTestUploadFile(string $name): UploadedFile
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        $handler = fopen($dir . $name . '.txt', 'w');
        fwrite($handler, 'tes test tes test boop bop');
        fclose($handler);

        $file = new UploadedFile($dir . $name . '.txt', $name . '.txt');
        $this->createdFiles[] = $file;
        return $file;
    }

    private function createTestFile($name = "test.txt"): File {
        $file = new TestFile();
        $file->setPath('local/location');
        $file->setName($name);
        return $file;
    }

    public function setUp(): void {
        self::bootKernel(['debug' => false]);
        $this->provider = static::getContainer()->get('local_1');
        $this->dir = __DIR__.'/../../../var/files/local/location';
    }

    public function testPersist(): void {
        $file = $this->createTestFile();
        $upload = $this->createTestUploadFile("test");
        $file->setUploadedFileReference($upload);
        $this->provider->persist($file);
        $this->assertTrue(file_exists($this->dir.'/test.txt'));
    }

    public function tearDown(): void {

        foreach ($this->createdFiles as $file) {
            /**
             * @var UploadedFile $file
             */
            unlink($file->getPathname());
        }
        $this->createdFiles = [];
        rmdir($this->dir);
    }
}
