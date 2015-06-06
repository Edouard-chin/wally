<?php

namespace SocialWallBundle\Security\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use SocialWallBundle\SocialMediaType;

class ConfigVoter extends AbstractVoter
{
    const ADD_FACEBOOK_CONFIG = 'add_facebook_config';
    const REMOVE_FACEBOOK_CONFIG = 'remove_facebook_config';
    const ADD_INSTAGRAM_CONFIG = 'add_instagram_config';

    /**
     * {@inheritDoc}
     */
    protected function getSupportedClasses()
    {
        return ['SocialWallBundle\Entity\User', 'SocialWallBundle\Entity\SocialMediaConfig\FacebookConfig'];
    }

    /**
     * {@inheritDoc}
     */
    protected function getSupportedAttributes()
    {
        return [self::ADD_INSTAGRAM_CONFIG, self::REMOVE_FACEBOOK_CONFIG, self::ADD_FACEBOOK_CONFIG];
    }

    /**
     * {@inheritDoc}
     */
    protected function isGranted($attribute, $object, $user = null)
    {
        $accessTokens = $user->getAccessTokens();
        switch ($attribute) {
            case self::ADD_FACEBOOK_CONFIG:
                return isset($accessTokens[SocialMediaType::FACEBOOK]);
                break;

            case self::ADD_INSTAGRAM_CONFIG:
                return isset($accessTokens[SocialMediaType::INSTAGRAM]);
                break;

            case self::REMOVE_FACEBOOK_CONFIG:
                return $object->getUser() === $user;
                break;
        }
    }
}
