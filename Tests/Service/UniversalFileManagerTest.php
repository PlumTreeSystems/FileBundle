<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-29
 * Time: 21:28
 */

namespace PlumTreeSystems\FileBundle\Tests\Service;

use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use PlumTreeSystems\FileBundle\Model\FileSystemFactoryInterface;
use PlumTreeSystems\FileBundle\Provider\LocalFileProvider;
use PlumTreeSystems\FileBundle\Service\FileSystemFactory;
use PlumTreeSystems\FileBundle\Service\UniversalFileManager;
use PlumTreeSystems\FileBundle\Tests\Service\FileManagerTest\TestFile;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UniversalFileManagerTest extends TestCase
{

    /**
     * @var MockObject
     */
    private $entityManager;

    /**
     * @var MockObject
     */
    private $serviceLocator;

    private string $fileDir;

    /**
     * @var MockObject
     */
    private $router;

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

        $this->fileDir = sys_get_temp_dir().'/file_provider';

        $this->entityManager = $this
            ->getMockBuilder(\Doctrine\Persistence\ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();


        $this->serviceLocator = $this->getMockBuilder(ServiceLocator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceLocator->method('get')
            ->willReturn(new LocalFileProvider($this->fileDir, 'https://test.com'));

        $this->router = $this->getMockBuilder(UrlGeneratorInterface::class)
            ->getMock();
        $this->router->method('generate')
            ->willReturnCallback(fn($route, $args) => 'https://test.com/download/'.$args['id']);

        $this->fileManager = $this->buildFileManager($filesystemFactory);
    }

    private function buildFileManager(): UniversalFileManager {
        return new UniversalFileManager(
            $this->serviceLocator,
            ['local/location' => 'local_1'],
            'local_1',
            TestFile::class,
            $this->router,
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
        unlink($this->fileDir);
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
        $this->assertTrue(file_exists($this->fileDir.'/local/location/test.txt'));
        // read
        $this->assertEquals('data', $this->fileManager->read($file));
        // generateDownloadUrl
        $file->setId(1);
        $this->assertEquals('https://test.com/download/1', $this->fileManager->generateDownloadUrl($file));
        $file->addContext('public', 1);
        $this->assertEquals('https://test.com/local/location/test.txt', $this->fileManager->generateDownloadUrl($file));
        // create streamable uri
        $this->assertEquals('file://'.$this->fileDir.'/local/location/test.txt', $this->fileManager->createStreamableUri($file));
        
    }
   
}
