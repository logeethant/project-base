<?php

namespace SS6\ShopBundle\Model\Customer;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Serializable;
use SS6\ShopBundle\Model\Security\Roles;
use SS6\ShopBundle\Model\Security\TimelimitLoginInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="SS6\ShopBundle\Model\Customer\SecurityUserRepository")
 */
class User implements UserInterface, TimelimitLoginInterface, Serializable {

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=100)
	 */
	protected $firstName;

	/**
	 * @ORM\Column(type="string", length=100)
	 */
	protected $lastName;
	
	/**
	 * @ORM\Column(type="string", length=255, unique=true)
	 * @Assert\Email(message = "E-mail '{{ value }}' není validní.")
	 */
	protected $email;

	/**
	 * @ORM\Column(name="password", type="string", length=100)
	 */
	protected $password;
	
	/**
	 * @var DateTime 
	 */
	protected $lastActivity;
	
	/**
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $email
	 */
	public function __construct($firstName, $lastName, $email) {
		$this->firstName = $firstName;
		$this->lastName = $lastName;
		$this->email = $email;
	}

	/**
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $email
	 */
	public function edit($firstName, $lastName, $email) {
		$this->firstName = $firstName;
		$this->lastName = $lastName;
		$this->email = $email;
	}
	
	/**
	 * @param string $password
	 */
	public function changePassword($password) {
		$this->password = $password;
	}
	
	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getFirstName() {
		return $this->firstName;
	}
	
	/**
	 * @return DateTime
	 */
	public function getLastActivity() {
		return $this->lastActivity;
	}
	
	/**
	 * @param DateTime $lastActivity
	 */
	public function setLastActivity($lastActivity) {
		$this->lastActivity = $lastActivity;
	}

	/**
	 * @return string
	 */
	public function getLastName() {
		return $this->lastName;
	}
	
	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}
	
	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->email;
	}

	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}
	
	/**
	 * @return string
	 */
	public function getFullName() {
		return $this->firstName . ' ' . $this->lastName;
	}

	/**
	 * @inheritDoc
	 */
	public function serialize() {
		return serialize(array(
			$this->id,
			$this->email,
			$this->password,
			time(), // lastActivity
		));
	}

	/**
	 * @inheritDoc
	 */
	public function unserialize($serialized) {
		list (
			$this->id,
			$this->email,
			$this->password,
			$timestamp
		) = unserialize($serialized);
		$this->lastActivity = new DateTime();
		$this->lastActivity->setTimestamp($timestamp);
	}

	/**
	 * @inheritDoc
	 */
	public function eraseCredentials() {
		
	}

	/**
	 * @inheritDoc
	 */
	public function getRoles() {
		return array(Roles::ROLE_CUSTOMER);
	}

	/**
	 * @inheritDoc
	 */
	public function getSalt() {
		return null; // bcrypt include salt in password hash
	}

}