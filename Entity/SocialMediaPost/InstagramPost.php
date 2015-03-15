<?php

namespace SocialWallBundle\Entity\SocialMediaPost;

use Doctrine\ORM\Mapping as ORM;

use SocialWallBundle\Entity\SocialMediaPost;

/**
 * @ORM\Entity
 */
class InstagramPost extends SocialMediaPost
{
    /**
     * @ORM\Column(name="minTagId", type="string", length=255)
     */
    private $minTagId;

    /**
     * @ORM\Column(name="tag", type="string", length=255)
     */
    private $tag;

    public function getType()
    {
        return SocialMediaPost::TYPE_INSTAGRAM;
    }

    public function getMinTagId()
    {
        return $this->minTagId;
    }

    public function setMinTagId($minTagId)
    {
        $this->minTagId = $minTagId;

        return $this;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }
}
