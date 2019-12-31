<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-26
 * Time: 19:35
 */

namespace PlumTreeSystems\FileBundle\Form\Type;

use PlumTreeSystems\FileBundle\Entity\File;
use Doctrine\ORM\PersistentCollection;
use PlumTreeSystems\FileBundle\Form\Transformer\PTSFileTransformer;
use PlumTreeSystems\FileBundle\Model\FileManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PTSFileType extends AbstractType
{

    private $transformer;
    private $fileManager;

    public function __construct(PTSFileTransformer $transformer, FileManagerInterface $fileManager)
    {
        $this->transformer = $transformer;
        $this->fileManager = $fileManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addModelTransformer($this->transformer)
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                $requestHandler = $form->getConfig()->getRequestHandler();
                $data = null;

                if ($options['multiple']) {
                    $initialData = $form->getData();
                    $data = [];
                    // we prepend initial data on pre submit
                    if ($options['expanded']) {
                        if ($initialData instanceof PersistentCollection) {
                            $initialData = $initialData->getValues();
                        }
                        if (isset($initialData)) {
                            $data = $initialData;
                        }
                    } else {
                        if ($initialData instanceof PersistentCollection) {
                            $initialData = $initialData->getValues();
                        }
                        if (isset($initialData)) {
                            foreach ($initialData as $datum) {
                                if ($options['deleteOrphans']) {
                                    $this->removeOldFile($datum);
                                }
                            }
                        }
                    }

                    foreach ($event->getData() as $file) {
                        if ($requestHandler->isFileUpload($file)) {
                            $data[] = $file;
                        }
                    }

                    // submitted data for an input file (not required) without choosing any file
                    if ([null] === $data || [] === $data) {
                        $emptyData = $form->getConfig()->getEmptyData();

                        $data = is_callable($emptyData) ? call_user_func($emptyData, $form, $data) : $emptyData;
                    }


                    $event->setData($data);
                } else {
                    if ($form->getData() !== null && $event->getData() === null) {
                        $event->setData($form->getData());
                        return;
                    }
                    if (!$requestHandler->isFileUpload($event->getData())) {
                        $emptyData = $form->getConfig()->getEmptyData();

                        $data = is_callable($emptyData) ? call_user_func($emptyData, $form, $data) : $emptyData;
                        $event->setData($data);
                    } elseif (isset($options['data'])) {
                        $initial = $options['data'];
                        if ($options['deleteOrphans']) {
                            $this->removeOldFile($initial);
                        }
                    } else {
                        //currently does not support nesting due to this area
                        if (!is_null($form->getData())) {
                            if ($options['deleteOrphans']) {
                                $this->removeOldFile($form->getData(), true);
                            }
                        }
                    }
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                $data = $form->getData();
                if ($options['multiple']) {
                    if (is_array($data)) {
                        foreach ($data as $datum) {
                            self::updateWithOptions($datum, $options);
                        }
                    }
                } else {
                    self::updateWithOptions($data, $options);
                }
            });
    }

    private static function updateWithOptions(?File $datum, $options)
    {
        if (!$datum) {
            return $datum;
        }
        if ($options['public']) {
            if (!$datum->getContextValue('public') ||
                ($datum->getContextValue('public') && $datum->getContextValue('public') != 1)
            ) {
                $datum->addContext('public', 1);
            }
        }
        if ($options['path'] && is_string($options['path'])) {
            if ($datum->getContextValue('path')) {
                $datum->removeContext('path');
            }
            if (!$datum->getContextValue('path')) {
                $datum->addContext('path', $options['path']);
            }
        }
        if ($options['saveExt']) {
            if (!$datum->getContextValue('saveExt')) {
                $datum->addContext('saveExt', $options['saveExt']);
            }
        }
        return $datum;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $object = $form->getData();
        $view->vars['object'] = $object;

        if (!$options['multiple']) {
            $view->vars['download_uri'] = null;

            if ($object && $object->getId() !== null) {
                $this->generateViewForExistingFile($view, $object);
            }
        } elseif (($object && is_array($object)) || ($object && $object instanceof PersistentCollection)) {
            $view->vars['download_uri'] = [];
            $view->vars['remove_uri'] = [];
            $view->vars['download_label'] = [];

            foreach ($object as $item) {
                /** @var $item File */
                if ($item->getId()) {
                    $this->generateViewForExistingFiles($view, $item);
                }
            }
        }
    }

    protected function generateViewForExistingFile($view, File $file)
    {
        $view->vars['download_uri'] = $this->fileManager->
            generateDownloadUrl($file);
        $view->vars['remove_uri'] = $this->fileManager->
            generateRemoveUrl($file, $_SERVER["REQUEST_URI"]);
        $view->vars = array_replace(
            $view->vars,
            [
                'download_label' => $file->getOriginalName(),
                'remove_label' => 'Remove'
            ]
        );
    }

    protected function generateViewForExistingFiles($view, File $file)
    {
        array_push($view->vars['download_uri'], $this->fileManager->
        generateDownloadUrl($file));

        array_push($view->vars['remove_uri'], $this->fileManager->
        generateRemoveUrl($file, $_SERVER["REQUEST_URI"]));

        array_push($view->vars['download_label'], $file->getOriginalName());

        $view->vars = array_replace(
            $view->vars,
            [
                'remove_label' => 'Remove'
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'expanded' => false,
            'public' => false,
            'deleteOrphans' => true,
            'path' =>  false,
            'saveExt' => false
        ]);
    }

    public function getParent()
    {
        return FileType::class;
    }

    private function removeOldFile($oldFile, $flush = false)
    {
        $this->fileManager->removeEntity($oldFile, $flush);
    }

    public function getName()
    {
        return 'pts_file';
    }

    public function getBlockPrefix()
    {
        return $this->getName();
    }
}
