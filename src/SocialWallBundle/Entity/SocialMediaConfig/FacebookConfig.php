<?php

namespace SocialWallBundle\Entity\SocialMediaConfig;

use Doctrine\ORM\Mapping as ORM;

use SocialWallBundle\Entity\SocialMediaConfig;
use SocialWallBundle\SocialMediaType;

/**
 * @ORM\Entity(repositoryClass="SocialWallBundle\Repository\FacebookConfigRepository")
 */
class FacebookConfig extends SocialMediaConfig
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $pageName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $pageId;

    /**
     * @return string
     */
    public function getType()
    {
        return SocialMediaType::FACEBOOK;
    }

    /**
     * @return string
     */
    public function getPageName()
    {
        return $this->pageName;
    }

    /**
     * @param string $pageName
     * @return this
     */
    public function setPageName($pageName)
    {
        $this->pageName = $pageName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * @param string $pageId
     * @return this
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;

        return $this;
    }
}
