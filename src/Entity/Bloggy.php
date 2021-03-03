<?php

namespace DovStone\Bundle\BlogAdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\GeneratedValue;

define('bloggies', "{$_SERVER['DATABASE_TABLES_PREFIX']}____bloggies");

/**
 * @ORM\Table(name=bloggies)
 * @ORM\Entity(repositoryClass="DovStone\Bundle\BlogAdminBundle\Repository\BundleBloggyRepository")
 */
class Bloggy
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $type;

    /**
     * @ORM\Column(type="text")
     */
    private $slug;

    /**
     * @ORM\Column(type="text")
     */
    private $info;

    /**
     * BelongsTo ==> ManyToOne
     * @ORM\ManyToOne(targetEntity="DovStone\Bundle\BlogAdminBundle\Entity\User", inversedBy="bloggies", fetch="EAGER")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated;


    public function __construct()
    {
        if (null === $this->getCreated()) {
            $this->setCreated(new \DateTime('now'));
        }
        $this->setUpdated(new \DateTime('now'));
    }


    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get belongsTo ==> ManyToOne
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set belongsTo ==> ManyToOne
     *
     * @return  self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get belongsTo ==> ManyToOne
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set belongsTo ==> ManyToOne
     *
     * @return  self
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get the value of info
     */
    public function getInfo()
    {
        return json_decode($this->info);
    }

    /**
     * Set the value of info
     *
     * @return  self
     */
    public function setInfo($info)
    {
        $utf8_ansi2 = array(
            "\u2019" =>"'",
            "\u00c0" =>"À",
            "\u00c1" =>"Á",
            "\u00c2" =>"Â",
            "\u00c3" =>"Ã",
            "\u00c4" =>"Ä",
            "\u00c5" =>"Å",
            "\u00c6" =>"Æ",
            "\u00c7" =>"Ç",
            "\u00c8" =>"È",
            "\u00c9" =>"É",
            "\u00ca" =>"Ê",
            "\u00cb" =>"Ë",
            "\u00cc" =>"Ì",
            "\u00cd" =>"Í",
            "\u00ce" =>"Î",
            "\u00cf" =>"Ï",
            "\u00d1" =>"Ñ",
            "\u00d2" =>"Ò",
            "\u00d3" =>"Ó",
            "\u00d4" =>"Ô",
            "\u00d5" =>"Õ",
            "\u00d6" =>"Ö",
            "\u00d8" =>"Ø",
            "\u00d9" =>"Ù",
            "\u00da" =>"Ú",
            "\u00db" =>"Û",
            "\u00dc" =>"Ü",
            "\u00dd" =>"Ý",
            "\u00df" =>"ß",
            "\u00e0" =>"à",
            "\u00e1" =>"á",
            "\u00e2" =>"â",
            "\u00e3" =>"ã",
            "\u00e4" =>"ä",
            "\u00e5" =>"å",
            "\u00e6" =>"æ",
            "\u00e7" =>"ç",
            "\u00e8" =>"è",
            "\u00e9" =>"é",
            "\u00ea" =>"ê",
            "\u00eb" =>"ë",
            "\u00ec" =>"ì",
            "\u00ed" =>"í",
            "\u00ee" =>"î",
            "\u00ef" =>"ï",
            "\u00f0" =>"ð",
            "\u00f1" =>"ñ",
            "\u00f2" =>"ò",
            "\u00f3" =>"ó",
            "\u00f4" =>"ô",
            "\u00f5" =>"õ",
            "\u00f6" =>"ö",
            "\u00f8" =>"ø",
            "\u00f9" =>"ù",
            "\u00fa" =>"ú",
            "\u00fb" =>"û",
            "\u00fc" =>"ü",
            "\u00fd" =>"ý",
            "\u00ff" =>"ÿ");
        
        $this->info = strtr(json_encode($info), $utf8_ansi2);

        return $this;
    }

    /**
     * Get the value of enabled
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set the value of enabled
     *
     * @return  self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get the value of created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set the value of created
     *
     * @return  self
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get the value of updated
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set the value of updated
     *
     * @return  self
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get the value of user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}
