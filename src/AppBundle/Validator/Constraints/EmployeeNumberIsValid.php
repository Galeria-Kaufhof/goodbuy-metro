<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EmployeeNumberIsValid extends Constraint
{
    public $message = 'Diese Mitarbeiternummer ist ungültig.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}
