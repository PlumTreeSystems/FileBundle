<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-26
 * Time: 19:14
 */

namespace PlumTreeSystems\FileBundle\Exception;

use PlumTreeSystems\FileBundle\Entity\File;

class InparsableFileException extends FileException
{
    public function __construct(
        $message = 'Function expected a child of "' . File::class . '"'
    ) {
        parent::__construct($message);
    }
}
