<?php

namespace SocialWallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SocialMediaConfig.
 *
 * @ORM\Entity(repositoryClass="SocialWallBundle\Repository\SocialMediaConfigRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *   "facebook"  = "SocialWallBundle\Entity\SocialMediaConfig\FacebookConfig",
 *   "twitter"   = "SocialWallBundle\Entity\SocialMediaConfig\TwitterConfig",
 *   "instagram" = "SocialWallBundle\Entity\SocialMediaConfig\InstagramConfig",
 * })
 */
abstract class SocialMediaConfig
{
    /**
     * @var int
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="socialMediaConfig")
     **/
    private $user;

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @return int
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
     *
     * @return this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @param \SocialWallBundle\Entity\User $user
     * @return SocialMediaConfig
     */
    public function setUser(\SocialWallBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return \SocialWallBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
