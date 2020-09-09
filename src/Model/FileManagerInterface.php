<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-27
 * Time: 15:31
 */

namespace PlumTreeSystems\FileBundle\Model;

use Gaufrette\Stream;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface FileManagerInterface
{
    public function getFileReference(\PlumTreeSystems\FileBundle\Entity\File $file):? \Gaufrette\File;

    public function read(\PlumTreeSystems\FileBundle\Entity\File $file): string;

    public function save(\PlumTreeSystems\FileBundle\Entity\File $file): \PlumTreeSystems\FileBundle\Entity\File;

    public function getByName(string $name): \PlumTreeSystems\FileBundle\Entity\File;

    public function getById(int $id): \PlumTreeSystems\FileBundle\Entity\File;

    public function remove(\PlumTreeSystems\FileBundle\Entity\File $file);

    public function removeEntity(\PlumTreeSystems\FileBundle\Entity\File $file, $flush = false);

    public function generateDownloadUrl(\PlumTreeSystems\FileBundle\Entity\File $file): string;

    public function generateRemoveUrl(\PlumTreeSystems\FileBundle\Entity\File $file, string $backUrl): string;

    public function createNewFile(): \PlumTreeSystems\FileBundle\Entity\File;

    public function createStreamableUri(\PlumTreeSystems\FileBundle\Entity\File $file): string;

    public function downloadFile(\PlumTreeSystems\FileBundle\Entity\File $file): Response;

    public function getProviderSettings();
}
