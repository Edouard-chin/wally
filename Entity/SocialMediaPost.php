<?php

namespace SocialWallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="SocialMediaPostRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *   "facebook"  = "SocialWallBundle\Entity\SocialMediaPost\FacebookPost",
 *   "twitter"   = "SocialWallBundle\Entity\SocialMediaPost\TwitterPost",
 *   "instagram" = "SocialWallBundle\Entity\SocialMediaPost\InstagramPost",
 * })
 */
abstract class SocialMediaPost
{
    const TYPE_FACEBOOK  = 'facebook';
    const TYPE_TWITTER   = 'twitter';
    const TYPE_INSTAGRAM = 'instagram';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $visible = false;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $retrieved;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $authorUsername;

    public function __construct()
    {
        $this->retrieved = new \DateTime();
    }

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
     * @param boolean $visible
     * @return SocialMediaPost
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * @param string $message
     * @return SocialMediaPost
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param \DateTime $created
     * @return SocialMediaPost
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param string $authorUsername
     * @return SocialMediaPost
     */
    public function setAuthorUsername($authorUsername)
    {
        $this->authorUsername = $authorUsername;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorUsername()
    {
        return $this->authorUsername;
    }
}
