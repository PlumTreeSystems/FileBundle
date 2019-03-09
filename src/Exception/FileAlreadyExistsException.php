<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-26
 * Time: 19:14
 */

namespace PlumTreeSystems\FileBundle\Exception;

class FileAlreadyExistsException extends FileException
{
    public function __construct(
        $message = "Trying to replace an already existing file with 'replace_file' being set to true"
    ) {
        parent::__construct($message);
    }
}
