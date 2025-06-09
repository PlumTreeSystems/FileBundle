<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-27
 * Time: 15:31
 */

namespace PlumTreeSystems\FileBundle\Model;

use Symfony\Component\HttpFoundation\Response;

interface FileManagerInterface
{
    public function read(\PlumTreeSystems\FileBundle\Entity\File $file): string;

    public function save(\PlumTreeSystems\FileBundle\Entity\File $file): \PlumTreeSystems\FileBundle\Entity\File;

    public function remove(\PlumTreeSystems\FileBundle\Entity\File $file): void;

    public function createNewFile(): \PlumTreeSystems\FileBundle\Entity\File;

    public function createStreamableUri(\PlumTreeSystems\FileBundle\Entity\File $file): string;

    public function downloadFile(\PlumTreeSystems\FileBundle\Entity\File $file): Response;

    /**
     * @return array<mixed>
     */
    public function getProviderSettings(): array;
}
