<?php

namespace SocialWallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessToken
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class AccessToken
{
    const TYPE_FACEBOOK  = 'facebook';
    const TYPE_INSTAGRAM = 'instagram';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="text")
     */
    private $token;

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
     * @param string $token
     * @return AccessToken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setTtype($type)
    {
        $this->type = $type;

        return $this;
    }
}
