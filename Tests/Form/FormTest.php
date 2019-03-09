<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-26
 * Time: 20:54
 */

namespace PlumTreeSystems\FileBundle\Tests\Form;

use PHPUnit\Framework\MockObject\MockObject;
use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Form\Transformer\PTSFileTransformer;
use PlumTreeSystems\FileBundle\Form\Type\PTSFileType;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\NativeRequestHandler;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FormTest extends TypeTestCase
{

    /**
     * @var MockObject
     */
    private $fileManager;

    private $transformer;

    private $createdFiles;

    private function createTestFile(string $name)
    {
//        $root =  getcwd().DIRECTORY_SEPARATOR.'..'
//            .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'
//            .DIRECTORY_SEPARATOR.'..';
//        $dir = $root.DIRECTORY_SEPARATOR.'..'
//            .DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'cache'
//            .DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR;
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR;
        $handler = fopen($dir.$name.'.txt', 'w');
        fwrite($handler, 'tes test tes test boop bop');
        fclose($handler);
        $file = new UploadedFile($dir.$name.'.txt', $name.'.txt');
        $this->createdFiles[] = $file;
        return $file;
    }

    public function setUp(): void
    {
        $this->createdFiles = [];

        $this->fileManager = $this
            ->getMockBuilder(FileManagerInterface::class)
            ->getMock();

        $this->transformer = new PTSFileTransformer($this->fileManager);
        parent::setUp();
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

    public function getExtensions()
    {
        $type = new PTSFileType($this->transformer, $this->fileManager);
        return [new PreloadedExtension([$type], [])];
    }

    public function testCreateViewSingle()
    {
        $formData = [];
        $form = $this->factory->create(PTSFileType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        $resolvedData = $form->getData();
        $this->assertNull($resolvedData);
    }

    public function testCreateViewMulti()
    {
        $formData = [];
        $form = $this->factory->create(PTSFileType::class, null, ['multiple' => true]);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        $resolvedData = $form->getData();
        $this->assertTrue(is_array($resolvedData));
    }

    /**
     * @dataProvider requestHandlerProvider
     * @param RequestHandlerInterface $requestHandler
     */
    public function testInitializedSingle(RequestHandlerInterface $requestHandler)
    {
        $this->fileManager
            ->expects($this->once())
            ->method('removeEntity')
            ->will($this->returnCallback(function ($argument) {
                /**
                 * @var File $argument
                 */
                $argument->setName('');
            }));

        $ptsFile = $this->getMockBuilder(File::class)
            ->enableOriginalConstructor()
            ->getMock();

        $ptsFile->expects($this->once())
            ->method('setName');

        $file = $this->createTestFile('newTestFile');
        $file2 = [
            'name' => $file->getBasename(),
            'size' => $file->getSize(),
            'type' => 'image/png',
            'tmp_name' => $file->getPathname(),
            'error' => 0
        ];

        $form = $this->factory->createBuilder(PTSFileType::class, $ptsFile)
            ->setRequestHandler($requestHandler)
            ->getForm();

        $submitData = null;
        if ($requestHandler instanceof HttpFoundationRequestHandler) {
            $submitData = $file;
        } elseif ($requestHandler instanceof NativeRequestHandler) {
            $submitData = $file2;
        }
        $form->submit($submitData);
        $this->assertTrue($form->isSynchronized());

        $resolvedData = $form->getData();
        $this->assertInstanceOf(File::class, $resolvedData);
    }

    /**
     * @dataProvider requestHandlerProvider
     * @param RequestHandlerInterface $requestHandler
     */
    public function testInitializedMulti(RequestHandlerInterface $requestHandler)
    {
        $ptsFile = $this->getMockBuilder(File::class)
            ->enableOriginalConstructor()
            ->getMock();
        $ptsFile2 = $this->getMockBuilder(File::class)
            ->enableOriginalConstructor()
            ->getMock();

        $initial = [$ptsFile, $ptsFile2];
        $form = $this->factory->createBuilder(PTSFileType::class, $initial, ['multiple' => true])
            ->setRequestHandler($requestHandler)
            ->getForm();

        $file = $this->createTestFile('testFile1new');
        $file2 = $this->createTestFile('testFile2new');

        $nativeFile = [
            'name' => $file->getBasename(),
            'size' => $file->getSize(),
            'type' => 'image/png',
            'tmp_name' => $file->getPathname(),
            'error' => 0
        ];

        $nativeFile2 = [
            'name' => $file2->getBasename(),
            'size' => $file2->getSize(),
            'type' => 'image/png',
            'tmp_name' => $file2->getPathname(),
            'error' => 0
        ];

        $submitData = [];
        if ($requestHandler instanceof HttpFoundationRequestHandler) {
            $submitData[] = $file;
            $submitData[] = $file2;
        } elseif ($requestHandler instanceof NativeRequestHandler) {
            $submitData[] = $nativeFile;
            $submitData[] = $nativeFile2;
        }
        $form->submit($submitData);
        $this->assertTrue($form->isSynchronized());

        $resolvedData = $form->getData();
        $this->assertTrue(is_array($resolvedData));
        $this->assertTrue(sizeof($resolvedData) === 4);
        $this->assertInstanceOf(File::class, $resolvedData[0]);
    }

    //native only with builder
    public function testNativeWithBuilder()
    {

        $this->fileManager
            ->expects($this->once())
            ->method('removeEntity')
            ->will($this->returnCallback(function ($argument) {
                /**
                 * @var File $argument
                 */
                $argument->setName('');
            }));

        $ptsFile = $this->getMockBuilder(File::class)
            ->enableOriginalConstructor()
            ->getMock();

        $ptsFile->expects($this->once())
            ->method('setName');

        $initial = [
            'file' => $ptsFile
        ];

        $form = $this->factory->createBuilder(FormType::class, $initial)
            ->add('file', PTSFileType::class)
            ->getForm();

        $tempFile = $this->createTestFile('asd');
        $file = [
            'name' => $tempFile->getBasename(),
            'size' => $tempFile->getSize(),
            'type' => 'image/png',
            'tmp_name' => $tempFile->getPathname(),
            'error' => 0
        ];
        $tempData = $form->getData();
        $form->submit(['file' => $file]);

        $this->assertTrue($form->isSynchronized());
        $resolvedData = $form->getData();
        $this->assertInstanceOf(File::class, $resolvedData['file']);
    }

    public function requestHandlerProvider()
    {
        return [
            [new HttpFoundationRequestHandler()],
            [new NativeRequestHandler()]
        ];
    }
}
