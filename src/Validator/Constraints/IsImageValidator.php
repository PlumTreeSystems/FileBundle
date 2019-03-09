<?php
/**
 * Created by PhpStorm.
 * User: Marius
 * Date: 9/6/2018
 * Time: 08:47 PM
 */

namespace PlumTreeSystems\FileBundle\Validator\Constraints;

use PlumTreeSystems\FileBundle\Entity\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsImageValidator extends ConstraintValidator
{

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof File) {
            throw new UnexpectedTypeException($value, File::class);
        }

        $uploadedFile = $value->getUploadedFileReference();
        if ($uploadedFile !== null &&
            !preg_match('/image\/.*/', $uploadedFile->getClientMimeType())
        ) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
