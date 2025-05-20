<?php

namespace PlumTreeSystems\FileBundle\Service;

use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Provider\FileProviderInterface;
use PlumTreeSystems\FileBundle\Exception\ProviderNotFoundException;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Response;

class UniversalFileManager implements FileManagerInterface
{
    /**
     *
     * @var FileProviderInterface[]
     */
    protected iterable $fileProviders;

    public function __construct(
        #[AutowireLocator('pts.file.provider')]
        private ServiceLocator $locator,
        private array $fileProviderMap,
        private string $defaultProvider,
        private string $ptsFileExtendedEntity,
    ) {
    }

    /**
     * @throws ProviderNotFoundException
     */
    protected function grabProvider(File $file): FileProviderInterface
    {
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
                "File provider for path " . $file->getPath() . " was not found and default provider is not set."
            );
        }

        $provider = $this->locator->get($this->defaultProvider);
        if (!$provider) {
            throw new ProviderNotFoundException(
                "File provider default provider was not found. Maybe forgot to tag with 'pts.file.provider'?"
            );
        }
        return $provider;
    }

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
            }
        }
        $uploadedFile = $file->getUploadedFileReference();
        $hashName = md5(time() . uniqid());

        $file->setOriginalName($uploadedFile->getClientOriginalName());
        if ($file->getContextValue('saveExt')) {
            ['extension' => $extension ] = pathinfo($file->getOriginalName());
            $hashName .= '.' . $extension;
        }
        if (!$file->getName()) {
            $file->setName($hashName);
        }
        $file->addContext('Content-Type', $uploadedFile->getMimeType());
        $file->addContext('filesize', $uploadedFile->getSize());
        $provider = $this->grabProvider($file);
        $provider->persist($file);
        return $file;
    }

    public function remove(File $file)
    {
        $provider = $this->grabProvider($file);
        $provider->remove($file);
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
        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file->getOriginalName() . '";');
        $response->headers->set('Content-Type', 'application/force-download');

        $response->sendHeaders();
        readfile($this->createStreamableUri($file));
        return $response;
    }

    /**
     * @throws ProviderNotFoundException
     */
    public function getAuthorizedRemoteUri(File $file): ?string
    {
        $provider = $this->grabProvider($file);
        return $provider->getAuthorizedRemoteUri($file);
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
