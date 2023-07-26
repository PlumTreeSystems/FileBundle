<?php

namespace PlumTreeSystems\FileBundle\Service;

use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Provider\FileProviderInterface;
use Gaufrette\File as GaufretteFile;
use PlumTreeSystems\FileBundle\Exception\ProviderNotFoundException;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;

class UniversalFileManager implements FileManagerInterface
{

    /**
     * 
     * @var FileProviderInterface[]
     */
    protected iterable $fileProviders;

    public function __construct(
        #[TaggedLocator('pts.file.provider')]
        private ServiceLocator $locator,
        private array $fileProviderMap,
        private string $defaultProvider,
        private string $ptsFileExtendedEntity,
        private UrlGeneratorInterface $router,
    ) { }

    protected function grabProvider(File $file): FileProviderInterface {
        foreach ($this->fileProviderMap as $path => $service) {
            if (str_starts_with($file->getPath(), $path)) {
                $provider = $this->locator->get($service);
                if (!$provider) {
                    throw new ProviderNotFoundException(
                        "File provider $service was not found. Maybe forgot to tag with 'pts.file.provider'?"
                    );
                }
                return $provider;
            }
        }

        if (!$this->defaultProvider) {
            throw new ProviderNotFoundException(
                "File provider for path ".$file->getPath()." was not found and default provider is not set."
            );
        }

        $provider = $this->locator->get($this->defaultProvider);
        if (!$provider) {
            throw new ProviderNotFoundException(
                "File provider $service was not found. Maybe forgot to tag with 'pts.file.provider'?"
            );
        }
        return $provider;

    }
    
    public function getFileReference(File $file): ?GaufretteFile { return null; }

    public function read(File $file): string
    {
        $provider = $this->grabProvider($file);
        $uri = $provider->getStreamableUri($file);
        return file_get_contents($uri);
    }

    public function save(File $file, bool $uniqueName = true): File
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
        $hashName = md5(time().uniqid());

        $file->setOriginalName($uploadedFile->getClientOriginalName());
        if ($file->getContextValue('saveExt')) {
            ['extension' => $extension ] = pathinfo($file->getOriginalName());
            $hashName .= '.'.$extension;
        }
        if (!$file->getName()) {
            $file->setName($hashName);
        }
        $file->addContext('Content-Type', $uploadedFile->getMimeType());
        $file->addContext('filesize', $uploadedFile->getSize());
        $provider = $this->grabProvider($file);
        $provider->persist($file);
        $file->updateFileReference($this);
        return $file;
    }

    public function getByName(string $name): File
    {
        throw new NotImplementedException('Deprecated');
    }

    public function getById($id): File
    {
        throw new NotImplementedException('Deprecated');
    }

    public function remove(File $file)
    {
        $provider = $this->grabProvider($file);
        $provider->remove($file);
    }

    public function removeEntity(File $file, $flush = false)
    {
        throw new NotImplementedException('Deprecated');
    }

    public function generateDownloadUrl(File $file): string
    {

        if ($file->getContextValue('public') === '1') {
            $provider = $this->grabProvider($file);
            return $provider->getRawRemoteUri($file);
        }

        $url = $this->router->generate(
            'pts_file_download',
            ['id' => $file->getId()]
        );
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
        return new $this->ptsFileExtendedEntity();
    }

    public function createStreamableUri(File $file): string
    {
        $provider = $this->grabProvider($file);
        return $provider->getStreamableUri($file);
    }

    public function downloadFile(File $file): Response 
    {
        $fileRef = $this->getFileReference($file);
        if (!$fileRef) {
            throw new NotFoundHttpException('File: "'.$file->getName().'", was not found.');
        }
        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file->getOriginalName() . '";');
        $response->headers->set('Content-length', $fileRef->getSize());
        $response->headers->set('Content-Type', 'application/force-download');

        $response->sendHeaders();
        readfile($this->createStreamableUri($file));
        return $response;
    }

    public function getProviderSettings()
    {
        return [];
    }

    public function getSupportedProviders(): array
    {
        return array_keys($this->locator->getProvidedServices());
    }
}
