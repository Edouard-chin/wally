<?php

namespace SocialWallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\EntityListeners({"SocialWallBundle\EventListener\SocialMediaListener"})
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
     * @ORM\Column(type="text", nullable=true)
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

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $picture;

    public function __construct()
    {
        $this->retrieved = new \DateTime();
    }

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
     * @param bool $visible
     *
     * @return SocialMediaPost
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * @param string $message
     *
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
     *
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
     *
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

    /**
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * @param string $picture
     *
     * @return SocialMediaPost
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;

        return $this;
    }
}
