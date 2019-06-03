<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-29
 * Time: 11:53
 */

namespace PlumTreeSystems\FileBundle\Service;

use Doctrine\ORM\EntityManager;
use Gaufrette\StreamWrapper;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GaufretteFileManager implements FileManagerInterface
{
    /**
     * @var \Gaufrette\Filesystem
     */
    private $filesystem;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $class;

    /**
     * @var UrlGeneratorInterface $router
     */
    private $router;

    /**
     * GaufretteFileManager constructor.
     * @param FileSystemFactory $systemFactory
     * @param EntityManager $em
     * @param UrlGeneratorInterface $router
     * @param string $class
     */
    public function __construct(
        FileSystemFactory $systemFactory,
        EntityManager $em,
        UrlGeneratorInterface $router,
        string $class
    ) {
        $this->entityManager = $em;
        $this->filesystem = $systemFactory->getFileSystem();
        $this->router = $router;
        $this->class = $class;
    }

    private function randomString()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';
        for ($i = 0; $i < 10; $i++) {
            $randstring = $randstring . $characters[rand(0, strlen($characters)-1)];
        }
        return $randstring;
    }


    public function getFileReference(\PlumTreeSystems\FileBundle\Entity\File $file):? \Gaufrette\File
    {
        if ($this->filesystem->has($file->getName())) {
            return $this->filesystem->get($file->getName());
        }
        return null;
    }

    public function read(\PlumTreeSystems\FileBundle\Entity\File $file): string
    {
        return $this->filesystem->read($file->getName());
    }

    // Saves file to filesystem then returns PTS File entity
    public function save(\PlumTreeSystems\FileBundle\Entity\File $file): \PlumTreeSystems\FileBundle\Entity\File
    {
        $id = $file->getId();
        if (isset($id)) {
            $this->remove($file);
        }
        $uploadedFile = $file->getUploadedFileReference();
        $hashName = md5(time().$this->randomString());
        $fileEntity = $file;

        /**
         * @var \PlumTreeSystems\FileBundle\Entity\File $fileEntity
         */
        $fileEntity->setOriginalName($uploadedFile->getClientOriginalName());
        $fileEntity->setName($hashName);
        $fileEntity->addContext('Content-Type', $uploadedFile->getMimeType());
        $fileEntity->addContext('filesize', $uploadedFile->getSize());

        copy($uploadedFile->getPathname(), $this->createStreamableUri($fileEntity));

        $fileEntity->updateFileReference($this);
        return $fileEntity;
    }

    // Gets file entity which contains the file it is referencing (the reference to it, that is)
    public function getByName(string $name): \PlumTreeSystems\FileBundle\Entity\File
    {
        $file = $this->entityManager->getRepository($this->class)->findOneBy([$name]);
        /*
         * @var \PTS\FileBundle\Entity\File $file
         */
        $file->updateFileReference($this);
        return $file;
    }

    public function getById(int $id): \PlumTreeSystems\FileBundle\Entity\File
    {
        $file = $this->entityManager->getRepository($this->class)->find($id);
        /**
         * @var \PlumTreeSystems\FileBundle\Entity\File $file
         */
        $file->updateFileReference($this);
        return $file;
    }

    public function remove(\PlumTreeSystems\FileBundle\Entity\File $file)
    {
        if ($this->filesystem->has($file->getName())) {
            $this->filesystem->delete($file->getName());
        }
    }

    public function removeEntity(\PlumTreeSystems\FileBundle\Entity\File $file, $flush = false)
    {
        try {
            $this->remove($file);
            $this->entityManager->remove($file);
            if ($flush) {
                $this->entityManager->flush();
            }            
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function generateDownloadUrl(\PlumTreeSystems\FileBundle\Entity\File $file): string
    {
        $url = $this->router->generate(
            'pts_file_download',
            ['id' => $file->getId()]
        );
        //no route exception;
        return $url;
    }

    public function generateRemoveUrl(\PlumTreeSystems\FileBundle\Entity\File $file, string $backUrl = null): string
    {
        $arr = [
            'id' => $file->getId(),
        ];
        if ($backUrl && is_string($backUrl) && strlen($backUrl)) {
            $arr['backUrl'] = urlencode($backUrl);
        }
        $url = $this->router->generate(
            'pts_file_remove',
            $arr
        );

        return $url;
    }

    public function createNewFile(): \PlumTreeSystems\FileBundle\Entity\File
    {
        return new $this->class();
    }

    public function createStreamableUri(\PlumTreeSystems\FileBundle\Entity\File $file): string
    {
        $mapKey = 'root';
        $map = StreamWrapper::getFilesystemMap();
        $map->set($mapKey, $this->filesystem);

        StreamWrapper::register();
        return 'gaufrette://'.$mapKey.'/'.$file->getName();
    }

    public function downloadFile(\PlumTreeSystems\FileBundle\Entity\File $file): Response
    {
        $fileRef = $this->getFileReference($file);
        if (!$fileRef) {
            throw new NotFoundHttpException('File: "'.$file->getName().'", was not found.');
        }
        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
//        $response->headers->set('Content-type', $file->getContextValue('Content-Type'));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file->getOriginalName() . '";');
        $response->headers->set('Content-length', $fileRef->getSize());

        $response->sendHeaders();
        readfile($this->createStreamableUri($file));
        return $response;
    }
}
