<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-29
 * Time: 21:28
 */

namespace PlumTreeSystems\FileBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use PlumTreeSystems\FileBundle\Provider\LocalFileProvider;
use PlumTreeSystems\FileBundle\Service\UniversalFileManager;
use PlumTreeSystems\FileBundle\Tests\Service\FileManagerTest\TestFile;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UniversalFileManagerTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $serviceLocator;

    private string $fileDir;

    /**
     * @var FileManagerInterface
     */
    private $fileManager;

    private $createdFiles;

    private function createTestFile(string $name)
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        $handler = fopen($dir . $name . '.txt', 'w');
        fwrite($handler, 'data');
        fclose($handler);

        $file = new UploadedFile($dir . $name . '.txt', $name . '.txt');
        $this->createdFiles[] = $file;
        return $file;
    }

    public function setUp(): void
    {
        $this->createdFiles = [];

        $this->fileDir = sys_get_temp_dir() . '/file_provider';

        $this->serviceLocator = $this->getMockBuilder(ServiceLocator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceLocator->method('get')
            ->willReturn(new LocalFileProvider($this->fileDir, 'https://test.com'));

        $this->fileManager = $this->buildFileManager();
    }

    private function buildFileManager(): UniversalFileManager
    {
        return new UniversalFileManager(
            $this->serviceLocator,
            ['local/location' => 'local_1'],
            'local_1',
            TestFile::class,
        );
    }

    public function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            /**
             * @var UploadedFile $file
             */
            unlink($file->getPathname());
        }
        $this->createdFiles = null;
        rmdir($this->fileDir);
    }

    public function testManger()
    {
        // save
        $upload = $this->createTestFile('test');
        $file = new TestFile();
        $file->setUploadedFileReference($upload);
        $file->setPath('local/location');
        $file->setName('test.txt');
        $this->fileManager->save($file);

        $this->assertTrue(file_exists($this->fileDir . '/local/location/test.txt'));
        // read
        $this->assertEquals('data', $this->fileManager->read($file));

        // create streamable uri
        $this->assertEquals(
            'file://' . $this->fileDir . '/local/location/test.txt',
            $this->fileManager->createStreamableUri($file)
        );
    }
}
