<?php

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-11-27
 * Time: 15:37
 */

namespace PlumTreeSystems\FileBundle\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class File
{
    protected string $originalName;

    protected string $name;

    private UploadedFile $uploadedFileReference;

    protected string $context;

    protected string $path = '';

    protected mixed $dataStream = null;

    /**
     * File constructor.
     */
    public function __construct()
    {
        $this->context = json_encode([]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function addContext(string $key, string $value): void
    {
        $currentContext = $this->getContext();
        $currentContext[$key] = $value;
        $this->setContext(json_encode($currentContext));
    }

    public function removeContext(string $key): void
    {
        $currentContext = $this->getContext();
        unset($currentContext[$key]);
        $this->setContext(json_encode($currentContext));
    }

    public function getContextValue(string $key): ?string
    {
        $currentContext = $this->getContext();
        return isset($currentContext[$key])
            ? $currentContext[$key]
            : null;
    }

    /**
     * @return array<string, string>
     */
    public function getContext(): array
    {
        return json_decode($this->context, true);
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): void
    {
        $this->originalName = $originalName;
    }

    private function setContext(string $context): void
    {
        $this->context = $context;
    }

    abstract public function getId(): mixed;

    public function getUploadedFileReference(): UploadedFile
    {
        return $this->uploadedFileReference;
    }

    public function setUploadedFileReference(UploadedFile $uploadedFileReference): void
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

    public function setDataStream(mixed $dataStream): self
    {
        $this->dataStream = $dataStream;
        return $this;
    }

    public function getDataStream(): mixed
    {
        return $this->dataStream;
    }
}
