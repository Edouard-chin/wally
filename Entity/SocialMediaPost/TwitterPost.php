<?php

namespace SocialWallBundle\Entity\SocialMediaPost;

use Doctrine\ORM\Mapping as ORM;

use SocialWallBundle\Entity\SocialMediaPost;
use SocialWallBundle\SocialMediaType;

/**
 * @ORM\Entity
 */
class TwitterPost extends SocialMediaPost
{
    public function getType()
    {
        return SocialMediaType::TWITTER;
    }
}
