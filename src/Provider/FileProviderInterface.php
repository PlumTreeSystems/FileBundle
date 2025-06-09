<?php

namespace PlumTreeSystems\FileBundle\Provider;

use PlumTreeSystems\FileBundle\Entity\File;

interface FileProviderInterface
{
    // Persit file to the file system
    public function persist(File $file): void;

    // Remove file from the file system
    public function remove(File $file): void;

    // Create file stream
    public function getStreamableUri(File $file): string;

    // Get raw remote file location
    public function getRawRemoteUri(File $file): string;

    // Get pre signed and authorized file location
    public function getAuthorizedRemoteUri(File $file): ?string;
}
