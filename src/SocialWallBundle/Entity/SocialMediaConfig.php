<?php

namespace SocialWallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SocialMediaConfig
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *   "facebook"  = "SocialWallBundle\Entity\SocialMediaConfig\FacebookConfig",
 *   "twitter"   = "SocialWallBundle\Entity\SocialMediaConfig\TwitterConfig",
 *   "instagram" = "SocialWallBundle\Entity\SocialMediaConfig\InstagramConfig",
 * })
 * @ORM\Entity
 */
abstract class SocialMediaConfig
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $token;

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }
}
