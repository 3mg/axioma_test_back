<?php
/**
 * Created by PhpStorm.
 * User: nickolay
 * Date: 28.06.16
 * Time: 18:29
 */

namespace AppBundle\Entity;


interface TagableInterface
{
    /**
     * Get tags
     *
     * @param null $locale
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags($locale = null);

    /**
     * Add tag
     *
     * @param \AppBundle\Entity\Tag $tag
     *
     * @return TagableInterface
     */
    public function addTag(\AppBundle\Entity\Tag $tag);

    /**
     * Set tags
     *
     * @param \AppBundle\Entity\Tag[] $tags
     *
     * @return TagableInterface
     */
    public function setTags($tags);

    /**
     * Remove tag
     *
     * @param \AppBundle\Entity\Tag $tag
     */
    public function removeTag(\AppBundle\Entity\Tag $tag);
}