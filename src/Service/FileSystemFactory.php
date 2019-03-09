<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-28
 * Time: 20:37
 */

namespace PlumTreeSystems\FileBundle\Service;

use Gaufrette\Adapter;
use Gaufrette\Filesystem;
use PlumTreeSystems\FileBundle\Model\FileSystemFactoryInterface;

class FileSystemFactory implements FileSystemFactoryInterface
{
    private $filesystem;
    private $adapter;

    /**
     * AdapterFactory constructor.
     */
    public function __construct($provider, $config)
    {
        switch ($provider) {
            case 'local':
                $this->adapter = new Adapter\Local($config['directory'], true);
                $this->filesystem = new Filesystem($this->adapter);
        }
    }

    public function getFileSystem(): Filesystem
    {
        return $this->filesystem;
    }
}
