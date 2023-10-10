<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-29
 * Time: 21:28
 */

namespace PlumTreeSystems\FileBundle\Tests\Service;

use Doctrine\Persistence\ObjectRepository;
use Gaufrette\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use PlumTreeSystems\FileBundle\Model\FileSystemFactoryInterface;
use PlumTreeSystems\FileBundle\Service\FileSystemFactory;
use PlumTreeSystems\FileBundle\Service\GaufretteFileManager;
use PlumTreeSystems\FileBundle\Tests\Service\FileManagerTest\TestFile;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FileManagerTest extends TypeTestCase
{
    /**
     * @var MockObject
     */
    private $filesystem;

    /**
     * @var MockObject
     */
    private $entityManager;

    /**
     * @var string
     */
    private $class;

    /**
     * @var MockObject
     */
    private $router;

    /**
     * @var FileManagerInterface
     */
    private $fileManager;

    private RequestStack $rs;

    /**
     * @var array
     */
    private $config;

    private $createdFiles;

    private function createTestFile(string $name)
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        $handler = fopen($dir . $name . '.txt', 'w');
        fwrite($handler, 'tes test tes test boop bop');
        fclose($handler);

        $file = new UploadedFile($dir . $name . '.txt', $name . '.txt');
        $this->createdFiles[] = $file;
        return $file;
    }

    public function setUp(): void
    {
        $this->config = [
            'directory' => $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR
        ];

        $this->createdFiles = [];

        $filesystemFactory = $this
            ->getMockBuilder(FileSystemFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem = $this
            ->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemFactory
            ->expects($this->once())
            ->method('getFileSystem')
            ->willReturn($this->filesystem);

        $this->entityManager = $this
            ->getMockBuilder(\Doctrine\Persistence\ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->class = TestFile::class;

        $this->router = $this->getMockBuilder(UrlGeneratorInterface::class)
            ->getMock();

        $this->rs = $this->getMockBuilder(RequestStack::class)
            ->getMock();

        $this->fileManager = $this->buildFileManager($filesystemFactory);
    }

    private function buildFileManager(FileSystemFactoryInterface $fs): GaufretteFileManager
    {
        return new GaufretteFileManager(
            $fs,
            $this->entityManager,
            $this->router,
            $this->rs,
            [],
            $this->class,
            "local"
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
    }

    public function testGetFileReference()
    {
        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $this->filesystem
            ->expects($this->once())
            ->method('get')
            ->willReturn($this
                ->getMockBuilder(\Gaufrette\File::class)
                ->disableOriginalConstructor()
                ->getMock());

        $file = new $this->class();

        $this->fileManager->getFileReference($file);
    }

    public function testRead()
    {
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->willReturn('path');

        $file = new $this->class();

        $this->fileManager->read($file);
    }

    public function testSave()
    {
        $file = new $this->class();
        $fileSystemFactory =
            new FileSystemFactory(
                'local',
                $this->config
            );

        $this->fileManager = $this->buildFileManager($fileSystemFactory);

        /**
         * @var File $file
         */
        $uploadedFile = $this->createTestFile('testfile');
        $file->setUploadedFileReference($uploadedFile);
        $returned = $this->fileManager->save($file);

        $this->assertNotNull($file->getName());

        $this->assertEquals(
            file_get_contents($uploadedFile->getPathname()),
            file_get_contents($this->config['directory'] . $returned->getName())
        );
    }

    public function testGetByName()
    {

        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $this->filesystem
            ->expects($this->once())
            ->method('get')
            ->willReturn($this
                ->getMockBuilder(\Gaufrette\File::class)
                ->disableOriginalConstructor()
                ->getMock());

        $mockpository = $this
            ->getMockBuilder(ObjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockpository
            ->expects($this->any())
            ->method('findOneBy')
            ->with($this->isType('array'))
            ->willReturn(new $this->class());

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockpository);

        $file = $this->fileManager->getByName('name');
        $this->assertTrue(is_a($file, File::class));
    }

    public function testGetById()
    {
        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $this->filesystem
            ->expects($this->once())
            ->method('get')
            ->willReturn($this
                ->getMockBuilder(\Gaufrette\File::class)
                ->disableOriginalConstructor()
                ->getMock());

        $mockpository = $this
            ->getMockBuilder(ObjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockpository
            ->expects($this->any())
            ->method('find')
            ->with($this->isType('int'))
            ->willReturn(new $this->class());

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockpository);

        $file = $this->fileManager->getById(1);
        $this->assertTrue(is_a($file, File::class));
    }

    public function testRemove()
    {
        $file = new $this->class();
        $file->setName('test');

        $this->filesystem
            ->expects($this->atLeastOnce())
            ->method('has')
            ->willReturn(true);

        $this->filesystem
            ->expects($this->once())
            ->method('delete')
            ->with($this->isType('string'));

        $this->fileManager->remove($file);
    }

    public function testRemoveEntity()
    {
        $file = new $this->class();
        $file->setName('test');

        $this->filesystem
            ->expects($this->atLeastOnce())
            ->method('has')
            ->willReturn(true);

        $this->filesystem
            ->expects($this->exactly(2))
            ->method('delete')
            ->with($this->isType('string'));

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('remove');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->fileManager->removeEntity($file);
        $this->fileManager->removeEntity($file, true);
    }

    public function testGenerateDownloadUrl()
    {
        $file = new $this->class();
        $url = 'url';
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with($this->isType('string'), $this->isType('array'))
            ->willReturn($url);

        $returned = $this->fileManager->generateDownloadUrl($file);
        $this->assertEquals($url, $returned);
    }

    public function testGenerateRemoveUrl()
    {
        $file = new $this->class();
        $url = 'url';
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with($this->isType('string'), $this->isType('array'))
            ->willReturn($url);

        $returned = $this->fileManager->generateRemoveUrl($file, "");
        $this->assertEquals($url, $returned);
    }

    public function testCreateNewFile()
    {
        $file = $this->fileManager->createNewFile();
        $this->assertTrue(is_a($file, File::class));
    }

    /*
     * Tests the ability to download file
     *
     * Test is performed by:
     *  - Saving the file,
     *  - Recording the output data, outputted with the download file method,
     *  - Comparing recorded data to the original file's contents,
     *  - Checking if the Response has necessary file download headers
     */
    public function testDownloadFile()
    {
        $file = new $this->class();
        $fileSystemFactory =
            new FileSystemFactory(
                'local',
                $this->config
            );

        $this->fileManager = $this->buildFileManager($fileSystemFactory);

        /**
         * @var File $file
         */
        $uploadedFile = $this->createTestFile('testfile');
        $file->setUploadedFileReference($uploadedFile);
        $returned = $this->fileManager->save($file);

        ob_start();
        $response = $this->fileManager->downloadFile($returned);
        $contents = ob_get_clean();

        $this->assertEquals($contents, file_get_contents($uploadedFile));

        $this->assertEquals(
            $response->headers->get('Content-Disposition'),
            'attachment; filename="' . $returned->getOriginalName() . '";'
        );

        $this->assertEquals(
            $response->headers->get('Cache-Control'),
            'private'
        );
        $this->assertEquals(
            $response->headers->get('Content-length'),
            $file->getContextValue('filesize')
        );
    }
}
