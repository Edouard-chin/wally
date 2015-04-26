<?php

namespace SocialWallBundle\Entity\SocialMediaConfig;

use Doctrine\ORM\Mapping as ORM;

use SocialWallBundle\Entity\SocialMediaConfig;
use SocialWallBundle\SocialMediaType;

/**
 * @ORM\Entity
 */
class InstagramConfig extends SocialMediaConfig
{
    /**
     * @var array
     *
     * @ORM\Column(name="tags", type="simple_array", nullable=true)
     */
    private $tags;

    public function getType()
    {
        return SocialMediaType::INSTAGRAM;
    }

    /**
     * @param array $tags
     * @return SocialMediaConfig
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }
}