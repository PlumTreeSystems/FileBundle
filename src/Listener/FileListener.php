<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-25
 * Time: 19:56
 */

namespace PlumTreeSystems\FileBundle\Listener;

use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Exception\FileAlreadyExistsException;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FileListener implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var bool
     */
    private $replace;

    /**
     * FileListener constructor.
     * @param ContainerInterface $container
     * @param bool $replace
     */
    public function __construct(ContainerInterface $container, bool $replace)
    {
        $this->container = $container;
        $this->replace = $replace;
    }

    public function prePersist(File $file)
    {
        $this->save($file);
    }

    public function preUpdate(File $file)
    {
        $this->save($file, $this->replace);
    }

    public function preRemove(File $file)
    {
        $this->remove($file);
    }

    private function remove(File $file)
    {
        $this->container->get('pts_file.manager')->remove($file);
    }

    private function save(File $file, bool $replace = null)
    {
        if (isset($replace) && $replace) {
            $unmodified = $this->container->get('pts_file.manager')->getById($file->getId());
            if (isset($unmodified)) {
                throw new FileAlreadyExistsException();
            }
        }
        $prefixPath = $this->container->getParameter('pts_file_prefix_path');
        $path = $file->getContextValue('path');
        if ($prefixPath) {
            if ($path) {
                $path = $prefixPath.$path;
                $file->removeContext('path');
            } else {
                $path = $prefixPath;
            }
            $file->addContext('path', $path);
        }
        $this->container->get('pts_file.manager')->save($file);
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
