<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Entity\Customer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EmployeeNumberIsValidValidator extends ConstraintValidator
{
    public function validate($customer, Constraint $constraint)
    {
        if (
            $customer->getSalesdivision() === Customer::SALESDIVISION_CASH_CARRY
            && !preg_match('/^123$/', $customer->getEmployeeNumber())
        ) {
            $this->fail($constraint);
        }

        if (
            $customer->getSalesdivision() === Customer::SALESDIVISION_MEDIAMARKT_SATURN
            && !preg_match('/^456$/', $customer->getEmployeeNumber())
        ) {
            $this->fail($constraint);
        }

        if (
            $customer->getSalesdivision() === Customer::SALESDIVISION_METRO_GROUP_LOGISTIK
            && !preg_match('/^789$/', $customer->getEmployeeNumber())
        ) {
            $this->fail($constraint);
        }
    }

    private function fail(Constraint $constraint) {
        $this->context->buildViolation($constraint->message)
            ->atPath('employeeNumber')
            ->addViolation();
    }
}
