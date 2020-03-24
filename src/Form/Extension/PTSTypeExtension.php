<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 2017-12-23
 * Time: 18:23
 */

namespace PlumTreeSystems\FileBundle\Form\Extension;

use PlumTreeSystems\FileBundle\Form\Transformer\PTSFileTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PTSTypeExtension extends AbstractTypeExtension
{

    private $transformer;

    public function __construct(PTSFileTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (isset($options['pts_file'])) {
            $builder->addModelTransformer($this->transformer);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['pts_file']);
    }


    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return FileType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }
}
