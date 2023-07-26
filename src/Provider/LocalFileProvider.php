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
    ) {}

    public function getAuthorizedRemoteUri(File $file): ?string 
    {
        return $this->pubDirUrl.'/'.$file->getPath().'/'.$file->getName();
    }
 
    public function persist(File $file)
    {
        
        $ref = $file->getUploadedFileReference();
        if (!$ref) {
            throw new NoUploadedFileException("UploadedFileReference not attached to File");
        }
        
        if (!file_exists($this->dir)) {
            mkdir($this->dir);
        }

        $pathParts = explode('/', $file->getPath());
        $path = $this->dir;
        
        foreach($pathParts as $part) {
            $path .= '/'.$part;
            if (!file_exists($path)) {
                mkdir($path);
            }
        }
        $path .= '/'.$file->getName();
        if (!rename($ref->getPathname(), $path)){
            throw new \Exception("Failed to move file ".$ref->getPathname()." to $path");
        }

    }

    public function remove(File $file)
    {
        $location = $this->dir.'/'.$file->getPath().'/'.$file->getName();
        unlink($location);
    }

    public function getStreamableUri(File $file): string
    {
        $location = $this->dir.'/'.$file->getPath().'/'.$file->getName();
        return 'file://' . $location;
    }

    public function getRawRemoteUri(File $file): string 
    {
        return $this->getAuthorizedRemoteUri($file);
    }

}
