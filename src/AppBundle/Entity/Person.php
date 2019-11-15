<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use AppBundle\Entity\Ministry\ResponsibleAssignment;

/**
 * AppBundle\Entity\Person
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PersonRepository")
 * @ORM\Table(name="person")
 */
class Person
{
    const GENDER_MALE = 'm';
    const GENDER_FEMALE = 'f';

    /**
     * Persons whose working status depends on their age.
     */
    const WORKER_STATUS_DEPENDING = 1;

    /**
     * People younger than 65 years, but not able to work anymore.
     */
    const WORKER_STATUS_INVALID = 2;

    /**
     * People having other ministries like deacons or elders.
     */
    const WORKER_STATUS_OTHER_MINISTRIES = 3;

    /**
     * People not able to work, because of their residence.
     */
    const WORKER_STATUS_UNABLE_RESIDENCE = 4;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @Groups({"MinistryCategoryListing"})
     *
     * @var integer
     */
    private $id;

    /**
     * Last name can be empty. In that case the last name is taken from
     * address entity (family name)
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @var string
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=30)
     * @Groups({"MinistryCategoryListing"})
     *
     * @var string
     */
    private $firstname;

    /**
     * Date of birth
     *
     * @ORM\Column(type="date")
     * @Groups({"MinistryCategoryListing"})
     *
     * @var \DateTime
     */
    private $dob;
    
    /**
     * Gender ('m' or 'f')
     *
     * @ORM\Column(type="string", length=1)
     *
     * @var string
     */
    private $gender;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @var string
     */
    private $mobile;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @var string
     */
    private $phone2;

    /**
     * The label (deutsch: Beschriftung) of the second phone number.
     *
     * @ORM\Column(name="phone2_label", type="string", length=40, nullable=true)
     *
     * @var string
     */
    private $phone2Label;

    /**
     * @ORM\Column(name="maiden_name", type="string", length=40, nullable=true)
     *
     * @var string
     */
    private $maidenName;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Address", inversedBy="persons", cascade={"persist"})
     * @ORM\JoinColumn(name="address_id", nullable=false)
     * @Groups({"MinistryCategoryListing"})
     *
     * @var \AppBundle\Entity\Address
     */
    private $address;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\WorkingGroup", inversedBy="persons")
     * @ORM\JoinColumn(name="working_group_id", nullable=true, onDelete="SET NULL")
     *
     * @var \AppBundle\Entity\WorkingGroup
     */
    private $workingGroup;

    /**
     * Defines whether person is able to work or not.
     * See class constants self::WORKER_STATUS_*
     *
     * @ORM\Column(name="worker_status", type="smallint", nullable=false)
     *
     * @var int
     */
    private $workerStatus;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\WorkingGroup", mappedBy="leader")
     *
     * @var \AppBundle\Entity\WorkingGroup
     */
    private $leaderOf;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Ministry\ResponsibleAssignment", mappedBy="person", cascade={"remove"})
     *
     * @var \Doctrine\Common\Collections\Collection
     */
    private $ministryResponsibleAssignments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ministryResponsibleAssignments = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($lastname = null)
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getFirstnameAndLastname()
    {
        return $this->getFirstname() . ' ' . ($this->getLastname() ?: $this->getAddress()->getFamilyName());
    }

    public function getLastnameAndFirstname()
    {
        return ($this->getLastname() ?: $this->getAddress()->getFamilyName()) . ', ' . $this->getFirstname();
    }

    public function getLastnameFirstnameAndDob()
    {
        return sprintf(
            '%s, %s (%d)',
            $this->getLastname() ?: $this->getAddress()->getFamilyName(),
            $this->getFirstname(),
            $this->getDob()->format('Y')
        );
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return Person
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
     * Set the date of birth
     *
     * @param \DateTime $dob
     * @return Person
     */
    public function setDob($dob)
    {
        $this->dob = $dob;

        return $this;
    }

    /**
     * Get the date of birth
     *
     * @return \DateTime 
     */
    public function getDob()
    {
        return $this->dob;
    }

    /**
     * Get the current age of the person.
     * 
     * @return integer
     */
    public function getAge()
    {
        $diff = $this->getDob()->diff(new \DateTime(), true);
        /* @var $diff \DateInterval */

        return $diff->y;
    }
    
    public function getGender()
    {
        return $this->gender;
    }

    public function setGender($gender)
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     * @return Person
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string 
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Person
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
     * Set phone2
     *
     * @param string $phone2
     * @return Person
     */
    public function setPhone2($phone2)
    {
        $this->phone2 = $phone2;

        return $this;
    }

    /**
     * Get phone2
     *
     * @return string 
     */
    public function getPhone2()
    {
        return $this->phone2;
    }

    /**
     * Get the label (deutsch: Beschriftung) of the second phone number.
     * @return string
     */
    public function getPhone2Label()
    {
        return $this->phone2Label;
    }

    /**
     * Set the label (deutsch: Beschriftung) of the second phone number.
     * @param string $phone2Label
     * @return \AppBundle\Entity\Person
     */
    public function setPhone2Label($phone2Label = null)
    {
        $this->phone2Label = $phone2Label;
        return $this;
    }

    /**
     * Set maidenName
     *
     * @param string $maidenName
     * @return Person
     */
    public function setMaidenName($maidenName)
    {
        $this->maidenName = $maidenName;

        return $this;
    }

    /**
     * Get maidenName
     *
     * @return string 
     */
    public function getMaidenName()
    {
        return $this->maidenName;
    }

    /**
     * Set address
     *
     * @param \AppBundle\Entity\Address $address
     * @return Person
     */
    public function setAddress(\AppBundle\Entity\Address $address)
    {
        $this->address = $address;
        if (!$address->getPersons()->contains($this)) {
            $address->getPersons()->add($this);
        }
        return $this;
    }

    /**
     * Get address
     *
     * @return \AppBundle\Entity\Address 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set workingGroup
     *
     * @param \AppBundle\Entity\WorkingGroup $workingGroup
     * @return Person
     */
    public function setWorkingGroup(\AppBundle\Entity\WorkingGroup $workingGroup = null)
    {
        $this->workingGroup = $workingGroup;

        if ($workingGroup && !$workingGroup->getPersons()->contains($this)) {
            $workingGroup->getPersons()->add($this);
        }

        return $this;
    }

    /**
     * Get workingGroup
     *
     * @return \AppBundle\Entity\WorkingGroup 
     */
    public function getWorkingGroup()
    {
        return $this->workingGroup;
    }

    public function getWorkerStatus()
    {
        return $this->workerStatus;
    }

    public function setWorkerStatus($workerStatus)
    {
        $allStatus = self::getAllWorkerStatus();
        if (!isset($allStatus[$workerStatus])) {
            throw new \InvalidArgumentException('Given worker status is invalid.');
        }
        $this->workerStatus = $workerStatus;
        return $this;
    }

    public static function getAllWorkerStatus()
    {
        return array(
            self::WORKER_STATUS_DEPENDING => 'Depending on Age (< 65)',
            self::WORKER_STATUS_INVALID => 'Invalid/Sick',
            self::WORKER_STATUS_OTHER_MINISTRIES => 'Other Ministries',
            self::WORKER_STATUS_UNABLE_RESIDENCE => 'Residence far away',
        );
    }

    /**
     * @return WorkingGroup|null
     */
    public function getLeaderOf()
    {
        return $this->leaderOf;
    }

    /**
     * This method exists only for documentation.
     * 
     * @param \AppBundle\Entity\WorkingGroup $workingGroup
     * @throws \RuntimeException
     */
    public function setLeaderOf(\AppBundle\Entity\WorkingGroup $workingGroup = null)
    {
        throw new \RuntimeException('The leading person of a working group cannot be changed within person entity.');
    }

    public function getOptgroupLabelInWorkingGroupDropdown()
    {
        return $this->getWorkingGroup() ? 'Assigned' : 'Not assigned';
    }

    /**
     * @return Collection|ResponsibleAssignment[]
     */
    public function getMinistryResponsibleAssignments()
    {
        return $this->ministryResponsibleAssignments;
    }
}
