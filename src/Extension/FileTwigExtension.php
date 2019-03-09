<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-20
 * Time: 17:25
 */

namespace PlumTreeSystems\FileBundle\Extension;

use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Exception\InparsableFileException;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use Twig\Extension\AbstractExtension;

// {{ pts.fileParser(\PlumTreeSystems\FileBundle\Entity\File) }}
class FileTwigExtension extends AbstractExtension
{
    /**
     * @var FileManagerInterface $fileManager
     */
    private $fileManager;

    /**
     * FileTwigExtension constructor.
     * @param FileManagerInterface $fileManager
     */
    public function __construct(FileManagerInterface $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('downloadUrlParser', [$this, 'fileParser']),
            new \Twig_SimpleFilter('removeUrlParser', [$this, 'fileRemoveLink'])
        ];
    }
    public function fileParser($data)
    {
        if (!is_a($data, File::class)) {
            throw new InparsableFileException();
        }
        return $this->fileManager->generateDownloadUrl($data);
    }

    public function fileRemoveLink($data)
    {
        return $this->fileManager->generateRemoveUrl($data);
    }
}
