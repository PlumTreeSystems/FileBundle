<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-21
 * Time: 14:54
 */

namespace PlumTreeSystems\FileBundle\Form\Transformer;

use PlumTreeSystems\FileBundle\Form\Type\PTSFileType;
use Doctrine\ORM\PersistentCollection;
use PlumTreeSystems\FileBundle\Entity\File;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PTSFileTransformer implements DataTransformerInterface
{

    /**
     * @var FileManagerInterface $fileManager
     */
    private $fileManager;

    private $directory;

    /**
     * PTSFileTransformer constructor.
     * @param FileManagerInterface $fileManager
     */
    public function __construct(FileManagerInterface $fileManager, $options)
    {
        $this->fileManager = $fileManager;
        $this->directory = $options['directory'];
    }

    /**
     * Changes $value from display value to mapping value
     * Gets called on submit
     *
     * @param UploadedFile|array|File $value
     * @return null|File|array
     */
    public function reverseTransform($value)
    {
        if ($value == '' || $value === null) {
            return null;
        }
        if ($value instanceof File) {
            return $value;
        }
        if (is_array($value)) {
            if (isset($value['error']) && is_int($value['error'])) {
                $newFile = $this->fileManager->createNewFile();
                $newFile->setUploadedFileReference(new UploadedFile($value['tmp_name'], $value['name']));
                return $newFile;
            } elseif ($this->checkChildArray($value)) {
                $data = [];
                foreach ($value as $item) {
                    $newFile = $this->fileManager->createNewFile();
                    $newFile->setUploadedFileReference(new UploadedFile($item['tmp_name'], $item['name']));
                    $data[] = $newFile;
                }
                return $data;
            }
            $data = [];
            foreach ($value as $item) {
                if (is_a($item, File::class)) {
                    $data[] = $item;
                } else {
                    $newFile = $this->fileManager->createNewFile();
                    $newFile->setUploadedFileReference($item);
                    $data[] = $newFile;
                }
            }
            return $data;
        }
        $ptsFile = $this->fileManager->createNewFile();
        /**
         * @var File $ptsFile
         */
        $ptsFile->setUploadedFileReference($value);
        return $ptsFile;
    }

    /**
     * Changes $value from mapping value to display value
     * Gets called on view rendering
     *
     * @param File|array $value
     * @return null|\Symfony\Component\HttpFoundation\File\File|array
     */
    public function transform($value)
    {
        return null;
    }

    private function checkChildArray($arr)
    {
        if (is_array($arr)) {
            if (sizeof($arr) !== 0) {
                if (is_array($arr[0])) {
                    if (isset($arr[0]['error']) && is_int($arr[0]['error'])) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
