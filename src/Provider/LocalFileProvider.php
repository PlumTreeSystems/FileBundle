<?php

namespace PlumTreeSystems\FileBundle\Provider;

use PlumTreeSystems\FileBundle\Entity\File;

class LocalFileProvider implements FileProviderInterface
{
    public function __construct(
        protected string $dir,
        protected string $pubDirUrl = ''
    ) {
    }

    public function getAuthorizedRemoteUri(File $file): string
    {
        return $this->pubDirUrl . '/' . $file->getPath() . '/' . $file->getName();
    }

    public function persist(File $file): void
    {
        $stream = $file->getDataStream();

        if (!$stream) {
            $ref = $file->getUploadedFileReference();
            $stream = fopen($ref->getPathname(), 'r');
            if (false === $stream) {
                throw new \Exception("Failed to open stream for file reference " . $ref->getPathname());
            }
        }
        /** @var resource $stream */

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

    public function remove(File $file): void
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
