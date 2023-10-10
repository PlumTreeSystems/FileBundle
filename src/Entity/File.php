<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-27
 * Time: 15:37
 */

namespace PlumTreeSystems\FileBundle\Entity;

use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class File
{
    protected $originalName;

    protected $name;
    /**
     * @var \Gaufrette\File
     */
    private $fileReference;

    /**
     * @var UploadedFile
     */
    private $uploadedFileReference;

    protected $context;

    protected string $path = '';

    protected $dataStream = null;

    /**
     * File constructor.
     */
    public function __construct()
    {
        $this->context = json_encode([]);
    }

    public function updateFileReference(FileManagerInterface $fileManager)
    {
        trigger_deprecation("plumtreesystems/file-bundle", "2.1", "Update file reference is deprecated");
        $this->fileReference = $fileManager->getFileReference($this);
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function addContext(string $key, string $value)
    {
        $currentContext = $this->getContext();
        $currentContext[$key] = $value;
        $this->setContext(json_encode($currentContext));
    }

    public function removeContext(string $key)
    {
        $currentContext = $this->getContext();
        unset($currentContext[$key]);
        $this->setContext(json_encode($currentContext));
    }

    public function getContextValue(string $key)
    {
        $currentContext = $this->getContext();
        return isset($currentContext[$key])
            ? $currentContext[$key]
            : null;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return json_decode($this->context, true);
    }

    /**
     * @return mixed
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * @param mixed $originalName
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;
    }

    /**
     * @param string $context
     */
    private function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    abstract public function getId();

    /**
     * @return UploadedFile
     */
    public function getUploadedFileReference()
    {
        return $this->uploadedFileReference;
    }

    /**
     * @param UploadedFile $uploadedFileReference
     */
    public function setUploadedFileReference($uploadedFileReference)
    {
        $this->uploadedFileReference = $uploadedFileReference;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param $dataStream mixed
     */
    public function setDataStream($dataStream): self
    {
        $this->dataStream = $dataStream;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDataStream()
    {
        return $this->dataStream;
    }
}
