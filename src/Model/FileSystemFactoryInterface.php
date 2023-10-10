<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-27
 * Time: 15:30
 */

namespace PlumTreeSystems\FileBundle\Model;

use Gaufrette\Filesystem;

interface FileSystemFactoryInterface
{
    public function getFileSystem(): Filesystem;
}
