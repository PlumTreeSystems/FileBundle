<?php

/**
 * Created by PhpStorm.
 * User: Marius
 * Date: 9/6/2018
 * Time: 08:45 PM
 */

namespace PlumTreeSystems\FileBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class IsImage
 * @package PlumTreeSystems\FileBundle\Validator\Constraints
 * @Annotation
 */
class IsImage extends Constraint
{
    public $message = 'Only Images may be be uploaded.';
}
