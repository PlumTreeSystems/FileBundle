<?php

namespace PlumTreeSystems\FileBundle\Provider;

use Exception;
use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Exception\NoUploadedFileException;

class LocalFileProvider implements FileProviderInterface
{
    public function __construct(
        protected string $dir,
        protected string $pubDirUrl = ''
    ) {
    }

    public function getAuthorizedRemoteUri(File $file): ?string
    {
        return $this->pubDirUrl . '/' . $file->getPath() . '/' . $file->getName();
    }

    public function persist(File $file)
    {
        $stream = $file->getDataStream();

        if (!$stream) {
            $ref = $file->getUploadedFileReference();

            if (!$ref) {
                throw new NoUploadedFileException("UploadedFileReference not attached to File");
            }

            $stream = fopen($ref->getPathname(), 'r');
        }


        if (!file_exists($this->dir)) {
            mkdir(
                directory: $this->dir,
                recursive: true,
            );
        }

        $pathParts = explode('/', $file->getPath());
        $path = $this->dir;

        foreach ($pathParts as $part) {
            $path .= '/' . $part;
            if (!file_exists($path)) {
                mkdir($path);
            }
        }
        $path .= '/' . $file->getName();
        $newFile = fopen($path, 'w');

        if (!$newFile) {
            throw new \Exception("Failed to move file to $path");
        }

        stream_copy_to_stream($stream, $newFile);
        fclose($newFile);
    }

    public function remove(File $file)
    {
        $location = $this->dir . '/' . $file->getPath() . '/' . $file->getName();
        unlink($location);
    }

    public function getStreamableUri(File $file): string
    {
        $location = $this->dir . '/' . $file->getPath() . '/' . $file->getName();
        return 'file://' . $location;
    }

    public function getRawRemoteUri(File $file): string
    {
        return $this->getAuthorizedRemoteUri($file);
    }
}
