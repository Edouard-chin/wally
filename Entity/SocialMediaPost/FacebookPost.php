<?php

namespace SocialWallBundle\Entity\SocialMediaPost;

use Doctrine\ORM\Mapping as ORM;

use SocialWallBundle\Entity\SocialMediaPost;

/**
 * @ORM\Entity
 */
class FacebookPost extends SocialMediaPost
{
    public function getType()
    {
        return SocialMediaPost::TYPE_FACEBOOK;
    }
}
