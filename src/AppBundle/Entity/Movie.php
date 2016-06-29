<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Movie
 *
 * @ORM\Table(name="movie")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MovieRepository")
 */
class Movie
{
    use Translation\TranslatableTrait;

    const QUALITY_DVDRIP = 1;
    const QUALITY_HDRIP = 2;
    const QUALITY_BDRIP = 3;
    const QUALITY_720P = 4;
    const QUALITY_1080P = 5;
    const QUALITY_DVD5 = 6;

    public static function getQualities() {
        return [
            self::QUALITY_DVDRIP => "dvdrip",
            self::QUALITY_HDRIP => "hdrip",
            self::QUALITY_BDRIP => "bdrip",
            self::QUALITY_720P => "720p",
            self::QUALITY_1080P => "1080p",
            self::QUALITY_DVD5 => "dvd5",
        ];
    }

    public function getQualityString()
    {
        return self::getQualities()[$this->quality];
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="quality", type="smallint", nullable=true)
     */
    private $quality;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Actor", cascade={"persist", "refresh"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="book_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="actor_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $actors;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Tag", cascade={"persist", "refresh"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="book_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="actor_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $tags;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set quality
     *
     * @param integer $quality
     *
     * @return Movie
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * Get quality
     *
     * @return int
     */
    public function getQuality()
    {
        return $this->quality;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->actors = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add actor
     *
     * @param \AppBundle\Entity\Actor $actor
     *
     * @return Movie
     */
    public function addActor(\AppBundle\Entity\Actor $actor)
    {
        $this->actors[] = $actor;

        return $this;
    }
    /**
     * Set actors
     *
     * @param \AppBundle\Entity\Actor[] $actors
     *
     * @return Movie
     */
    public function setActors($actors)
    {
        $this->actors = new \Doctrine\Common\Collections\ArrayCollection($actors);

        return $this;
    }

    /**
     * Remove actor
     *
     * @param \AppBundle\Entity\Actor $actor
     */
    public function removeActor(\AppBundle\Entity\Actor $actor)
    {
        $this->actors->removeElement($actor);
    }

    /**
     * Get actors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActors()
    {
        return $this->actors;
    }

    /**
     * Add tag
     *
     * @param \AppBundle\Entity\Tag $tag
     *
     * @return Movie
     */
    public function addTag(\AppBundle\Entity\Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Set tags
     *
     * @param \AppBundle\Entity\Tag[] $tags
     *
     * @return Book
     */
    public function setTags($tags)
    {
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection($tags);

        return $this;
    }

    /**
     * Remove tag
     *
     * @param \AppBundle\Entity\Tag $tag
     */
    public function removeTag(\AppBundle\Entity\Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags($locale = null)
    {
        $currentLocale = $locale ? $locale : $this->getCurrentLocale();

        return $this->tags->filter(function(Tag $tag) use ($currentLocale) {
            return $tag->getLocale() == $currentLocale;
        });
    }
}
