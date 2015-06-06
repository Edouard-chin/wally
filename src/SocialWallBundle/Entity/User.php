<?php

namespace SocialWallBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $registeredAt;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $accessTokens;

    public function __construct()
    {
        $this->registeredAt = new \DateTime();
        parent::__construct();
    }

    /**
     * @return DateTime
     */
    public function getRegisteredAt()
    {
        return $this->registeredAt();
    }

    /**
     * @param DateTime $registeredAt
     *
     * @return User
     */
    public function setRegisteredAt($registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * @return array
     */
    public function getAccessTokens()
    {
        return $this->accessTokens;
    }

    /**
     * @param array $accessTokens
     *
     * @return User
     */
    public function setAccessTokens($accessTokens)
    {
        $this->accessTokens = $accessTokens;

        return $this;
    }

    /**
     * @param string $type  Which socialMedia this access was get from
     * @param string $token The acccess token
     *
     * @return User
     */
    public function addAccessToken($type, $token)
    {
        $this->accessTokens[$type] = $token;

        return $this;
    }
}
