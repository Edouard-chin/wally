<?php

namespace SocialWallBundle\Entity\SocialMediaConfig;

use Doctrine\ORM\Mapping as ORM;
use SocialWallBundle\Entity\SocialMediaConfig;
use SocialWallBundle\SocialMediaType;

/**
 * @ORM\Entity(repositoryClass="SocialWallBundle\Repository\InstagramConfigRepository")
 */
class InstagramConfig extends SocialMediaConfig
{
    /**
     * @var array
     *
     * @ORM\Column(name="tags", type="simple_array", nullable=true)
     */
    private $tags;

    /**
     * @var array
     *
     * @ORM\Column(name="subscriptions", type="simple_array", nullable=true)
     */
    private $subscriptions;

    public function getType()
    {
        return SocialMediaType::INSTAGRAM;
    }

    /**
     * @param array $tags
     *
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

    /**
     * @param string $hashtag
     */
    public function removeTag($hashtag)
    {
        $key = array_search($hashtag, $this->tags);
        unset($this->tags[$key]);
    }

    /**
     * @param string $hashtag
     */
    public function addTag($hashtag)
    {
        $this->tags[] = $hashtag;
    }

    /**
     * @return array
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * @param array $subscriptions
     *
     * @return InstagramConfig
     */
    public function setSubscriptions(array $subscriptions)
    {
        $this->subscriptions = $subscriptions;
    }

    /**
     * @param string $subscription
     */
    public function addSubscription($subscription)
    {
        $this->subscriptions[] = $subscription;
    }
}
