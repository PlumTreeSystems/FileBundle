<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-29
 * Time: 11:53
 */

namespace PlumTreeSystems\FileBundle\Service;

use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\EntityManager;
use Exception;
use Gaufrette\Filesystem;
use Gaufrette\StreamWrapper;
use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use PlumTreeSystems\FileBundle\PlumTreeSystemsFileBundle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GaufretteFileManager implements FileManagerInterface
{
    /**
     * @var Filesystem
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
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var array
     */
    private $providerSettings;

    /**
     * @var string
     */
    private $provider;

    /**
     * GaufretteFileManager constructor.
     * @param FileSystemFactory $systemFactory
     * @param EntityManager $em
     * @param UrlGeneratorInterface $router
     * @param RequestStack $requestStack
     * @param array $adapterSettings
     * @param string $class
     * @param string $provider
     */
    public function __construct(
        FileSystemFactory $systemFactory,
        EntityManager $em,
        UrlGeneratorInterface $router,
        RequestStack $requestStack,
        array $adapterSettings,
        string $class,
        string $provider
    ) {
        $this->entityManager = $em;
        $this->filesystem = $systemFactory->getFileSystem();
        $this->router = $router;
        $this->class = $class;
        $this->requestStack = $requestStack;
        $this->providerSettings = $adapterSettings;
        $this->provider = $provider;
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


    public function getFileReference(File $file):? \Gaufrette\File
    {

        $path = $file->getContextValue('path') ?? '';
        if ($this->filesystem->has($path.$file->getName())) {
            return $this->filesystem->get($path.$file->getName());
        }
        return null;
    }

    public function read(File $file): string
    {
        return $this->filesystem->read($file->getName());
    }

    public function savePublicFile(File $file): File
    {
        $file = $this->save($file);

        $file->addContext('public', '1');

        return $file;
    }

    // Saves file to filesystem then returns PTS File entity
    public function save(File $file): File
    {
        $id = $file->getId();
        if (isset($id)) {
            if ($file->getUploadedFileReference() !== null) {
                $this->remove($file);
            } else {
                return $file;
            }
        }
        $uploadedFile = $file->getUploadedFileReference();
        $hashName = md5(time().$this->randomString());
        $fileEntity = $file;

        /** @var File $fileEntity */
        $fileEntity->setOriginalName($uploadedFile->getClientOriginalName());
        if ($file->getContextValue('saveExt')) {
            ['extension' => $extension ] = pathinfo($fileEntity->getOriginalName());
            $hashName .= '.'.$extension;
        }
        $fileEntity->setName($hashName);
        $fileEntity->addContext('Content-Type', $uploadedFile->getMimeType());
        $fileEntity->addContext('filesize', $uploadedFile->getSize());

        copy($uploadedFile->getPathname(), $this->createStreamableUri($fileEntity));

        $fileEntity->updateFileReference($this);
        return $fileEntity;
    }

    // Gets file entity which contains the file it is referencing (the reference to it, that is)

    /**
     * @param string $name
     * @return File
     */
    public function getByName(string $name): File
    {
        $file = $this->entityManager->getRepository($this->class)->findOneBy([$name]);
        /* @var File $file */
        if (!$file) {
            throw new NotFoundHttpException("File not found by name: '".$name."'");
        }
        $file->updateFileReference($this);
        return $file;
    }

    public function getById(int $id): File
    {
        $file = $this->entityManager->getRepository($this->class)->find($id);
        /** @var File $file */
        if (!$file) {
            throw new NotFoundHttpException("File not found by id: '".$id."'");
        }
        $file->updateFileReference($this);
        return $file;
    }

    public function remove(File $file)
    {
        $path = $file->getContextValue('path') ?? '';
        if ($this->filesystem->has($path.$file->getName())) {
            $this->filesystem->delete($path.$file->getName());
        }
    }

    private function loadEntity(File $file)
    {
        if ($file instanceof Proxy) {
            if (!$file->__isInitialized()) {
                $file->__load();
            }
        }
    }

    /**
     * @param File $file
     * @param bool $flush
     * @throws Exception
     */
    public function removeEntity(File $file, $flush = false)
    {
        try {
            $this->loadEntity($file);
            $this->entityManager->remove($file);
            if ($flush) {
                $this->entityManager->flush();
            }
            $this->remove($file);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function generatePublicDownloadUrlForLocal(File $file)
    {
        $request = $this->requestStack->getCurrentRequest();
        $baseUrl = $request->getBaseUrl();

        $path = $file->getContextValue('path') ?? '';

        $fileKey = $file->getName();
        $downloadUrl = $baseUrl.$this->providerSettings['web_root'].'/'.$path.$fileKey;
        return $downloadUrl;
    }

    private function generatePublicDownloadUrlForS3(File $file)
    {
        $fileKey = $file->getName();
        $downloadUrl = 'https://s3.'.$this->providerSettings['region'].'.amazonaws.com/'.
            $this->providerSettings['bucket_name'].'/'.$fileKey;
        return $downloadUrl;
    }

    private function generatePublicDownloadUrlForGoogle(File $file)
    {
        $fileKey = $file->getName();
        $path = $file->getContextValue('path');
        return 'https://storage.cloud.google.com/'.
            $this->providerSettings['google_bucket'].'/'.$path.$fileKey;
    }

    public function generateDownloadUrl(File $file): string
    {
        if ($file->getContextValue('public') === '1') {
            switch ($this->provider) {
                case PlumTreeSystemsFileBundle::LOCAL_PROVIDER:
                    return $this->generatePublicDownloadUrlForLocal($file);
                case PlumTreeSystemsFileBundle::AWS_S3_PROVIDER:
                    return $this->generatePublicDownloadUrlForS3($file);
                case PlumTreeSystemsFileBundle::GOOGLE_STORAGE_CLOUD_PROVIDER:
                    return $this->generatePublicDownloadUrlForGoogle($file);
            }
        }

        $url = $this->router->generate(
            'pts_file_download',
            ['id' => $file->getId()]
        );
        //no route exception;
        return $url;
    }

    public function generateRemoveUrl(File $file, string $backUrl = null): string
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

    public function createNewFile(): File
    {
        return new $this->class();
    }

    public function createStreamableUri(File $file): string
    {
        $mapKey = 'root';
        $map = StreamWrapper::getFilesystemMap();
        $map->set($mapKey, $this->filesystem);

        $path = $file->getContextValue('path') ?? '';

        StreamWrapper::register();
        $streamableUri = 'gaufrette://'.$mapKey.'/'.$path.$file->getName();
        return $streamableUri;
    }

    public function downloadFile(File $file): Response
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
        $response->headers->set('Content-Type', 'application/force-download');

        $response->sendHeaders();
        readfile($this->createStreamableUri($file));
        return $response;
    }
}
