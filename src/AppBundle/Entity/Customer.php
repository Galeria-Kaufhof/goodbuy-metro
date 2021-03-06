<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Validator\Constraints as AdditionalAssert;

/**
 * Customer
 *
 * @ORM\Table(indexes={@ORM\Index(name="activated_coupons_idx", columns={"is_activated", "coupons_have_been_sent"})}, uniqueConstraints={@ORM\UniqueConstraint(name="salesdivision_employee_number", columns={"salesdivision", "employee_number"})})
 * @ORM\Entity
 *
 * @AdditionalAssert\EmployeeNumberIsValid
 */
class Customer
{
    const SALESDIVISION_CASH_CARRY = 0;
    const SALESDIVISION_MEDIAMARKT_SATURN = 1;
    const SALESDIVISION_METRO_GROUP_LOGISTIK = 2;

    const GREETING_MRS = 0;
    const GREETING_MR = 1;

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
     * @Assert\NotBlank(message = "Bitte geben Sie eine E-Mailadresse an.")
     * @Assert\Email(strict=true, message = "Diese E-Mailadresse ist ungültig.")
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="greeting", type="integer", nullable=true)
     */
    private $greeting;

    /**
     * @var string
     *
     * @Assert\Regex("/^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.'-]+$/u", message = "Die Angabe des Vornamens enthält ungültige Zeichen.")
     * @ORM\Column(name="firstname", type="string", length=255, nullable=true)
     */
    private $firstname;

    /**
     * @var string
     *
     * @Assert\Regex("/^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.'-]+$/u", message = "Die Angabe des Nachnamens enthält ungültige Zeichen.")
     * @ORM\Column(name="lastname", type="string", length=255, nullable=true)
     */
    private $lastname;

    /**
     * @var string
     *
     * @Assert\Regex("/^[0-9a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.'-]+$/u", message = "Die Angabe zu Straße und Hausnummer enthält ungültige Zeichen.")
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @Assert\Regex("/^[0-9]{5}$/", message = "Diese Postleitzahl ist ungültig.")
     * @ORM\Column(name="zipcode", type="string", length=5, nullable=true)
     */
    private $zipcode;

    /**
     * @var string
     *
     * @Assert\Regex("/^[0-9a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.'-]+$/u", message = "Die Angabe zur Stadt enthält ungültige Zeichen.")
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

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
     * @var boolean
     *
     * @ORM\Column(name="coupons_have_been_sent", type="boolean")
     */
    private $couponsHaveBeenSent;

    /**
     * @var datetime
     *
     * @ORM\Column(name="datetime_activation", type="datetime", nullable=true)
     */
    private $datetimeActivation;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_activation", type="string", length=15, nullable=true)
     */
    private $ipActivation;

    /**
     * @var boolean
     *
     * @ORM\Column(name="opt_in_accepted", type="boolean")
     */
    private $optInAccepted;

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
     * @var ArrayCollection|Couponcodes[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Couponcode", mappedBy="customer")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $couponcodes;

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
    public function setIsActivated($isActivated, $ip = null)
    {
        $this->isActivated = $isActivated;
        if (null === $this->getDatetimeActivation()) {
            $this->setDatetimeActivation(new \DateTime('now'));
        }

        if ($ip !== null) {
            $this->setIpActivation((string)$ip);
        }

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

    /**
     * Set greeting
     *
     * @param integer $greeting
     * @return Customer
     */
    public function setGreeting($greeting)
    {
        $this->greeting = $greeting;

        return $this;
    }

    /**
     * Get greeting
     *
     * @return integer 
     */
    public function getGreeting()
    {
        return $this->greeting;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->couponcodes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add couponcodes
     *
     * @param \AppBundle\Entity\Couponcode $couponcodes
     * @return Customer
     */
    public function addCouponcode(\AppBundle\Entity\Couponcode $couponcodes)
    {
        $this->couponcodes[] = $couponcodes;

        return $this;
    }

    /**
     * Remove couponcodes
     *
     * @param \AppBundle\Entity\Couponcode $couponcodes
     */
    public function removeCouponcode(\AppBundle\Entity\Couponcode $couponcodes)
    {
        $this->couponcodes->removeElement($couponcodes);
    }

    /**
     * Get couponcodes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCouponcodes()
    {
        return $this->couponcodes;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return Customer
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set zipcode
     *
     * @param string $zipcode
     * @return Customer
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * Get zipcode
     *
     * @return string 
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Customer
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set optInAccepted
     *
     * @param boolean $optInAccepted
     * @return Customer
     */
    public function setOptInAccepted($optInAccepted)
    {
        $this->optInAccepted = $optInAccepted;

        return $this;
    }

    /**
     * Get optInAccepted
     *
     * @return boolean 
     */
    public function getOptInAccepted()
    {
        return $this->optInAccepted;
    }

    /**
     * Set datetimeActivation
     *
     * @param \DateTime $datetimeActivation
     * @return Customer
     */
    public function setDatetimeActivation($datetimeActivation)
    {
        $this->datetimeActivation = $datetimeActivation;

        return $this;
    }

    /**
     * Get datetimeActivation
     *
     * @return \DateTime 
     */
    public function getDatetimeActivation()
    {
        return $this->datetimeActivation;
    }

    /**
     * Set ipActivation
     *
     * @param string $ipActivation
     * @return Customer
     */
    public function setIpActivation($ipActivation)
    {
        $this->ipActivation = $ipActivation;

        return $this;
    }

    /**
     * Get ipActivation
     *
     * @return string 
     */
    public function getIpActivation()
    {
        return $this->ipActivation;
    }

    /**
     * Set couponsHaveBeenSent
     *
     * @param boolean $couponsHaveBeenSent
     * @return Customer
     */
    public function setCouponsHaveBeenSent($couponsHaveBeenSent)
    {
        $this->couponsHaveBeenSent = $couponsHaveBeenSent;

        return $this;
    }

    /**
     * Get couponsHaveBeenSent
     *
     * @return boolean 
     */
    public function getCouponsHaveBeenSent()
    {
        return $this->couponsHaveBeenSent;
    }
}
