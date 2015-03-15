<?php

namespace SocialWallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SocialMediaConfig
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class SocialMediaConfig
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var array
     *
     * @ORM\Column(name="tags", type="simple_array")
     */
    private $tags;

    /**
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
