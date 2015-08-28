<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Customer
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Customer
{
    const SALESDIVISION_CASH_CARRY = 0;
    const SALESDIVISION_METRO_SATURN = 1;
    const SALESDIVISION_REAL = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255)
     */
    private $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="employee_number", type="string", length=32)
     */
    private $employeeNumber;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_activated", type="boolean")
     */
    private $isActivated;

    /**
     * @var string
     *
     * @ORM\Column(name="activation_code", type="string", length=40)
     */
    private $activationCode;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesdivision", type="integer")
     */
    private $salesdivision;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Customer
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return Customer
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return Customer
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set employeeNumber
     *
     * @param string $employeeNumber
     * @return Customer
     */
    public function setEmployeeNumber($employeeNumber)
    {
        $this->employeeNumber = $employeeNumber;

        return $this;
    }

    /**
     * Get employeeNumber
     *
     * @return string 
     */
    public function getEmployeeNumber()
    {
        return $this->employeeNumber;
    }

    /**
     * Set isActivated
     *
     * @param boolean $isActivated
     * @return Customer
     */
    public function setIsActivated($isActivated)
    {
        $this->isActivated = $isActivated;

        return $this;
    }

    /**
     * Get isActivated
     *
     * @return boolean 
     */
    public function getIsActivated()
    {
        return $this->isActivated;
    }

    /**
     * Set activationCode
     *
     * @param string $activationCode
     * @return Customer
     */
    public function setActivationCode($activationCode)
    {
        $this->activationCode = $activationCode;

        return $this;
    }

    /**
     * Get activationCode
     *
     * @return string 
     */
    public function getActivationCode()
    {
        return $this->activationCode;
    }

    /**
     * Set salesdivision
     *
     * @param integer $salesdivision
     * @return Customer
     */
    public function setSalesdivision($salesdivision)
    {
        $this->salesdivision = $salesdivision;

        return $this;
    }

    /**
     * Get salesdivision
     *
     * @return integer 
     */
    public function getSalesdivision()
    {
        return $this->salesdivision;
    }
}
