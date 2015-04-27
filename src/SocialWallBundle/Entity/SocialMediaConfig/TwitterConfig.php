<?php

namespace SocialWallBundle\Entity\SocialMediaConfig;

use Doctrine\ORM\Mapping as ORM;

use SocialWallBundle\Entity\SocialMediaConfig;
use SocialWallBundle\SocialMediaType;

/**
 * @ORM\Entity
 */
class TwitterConfig extends SocialMediaConfig
{
    public function getType()
    {
        return SocialMediaType::TWITTER;
    }
}
