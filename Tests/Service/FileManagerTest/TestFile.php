<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2018-01-01
 * Time: 20:42
 */

namespace PlumTreeSystems\FileBundle\Tests\Service\FileManagerTest;

use PlumTreeSystems\FileBundle\Entity\File;

class TestFile extends File
{
    private ?string $id = null;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(?string $id)
    {
        $this->id = $id;
    }
}
