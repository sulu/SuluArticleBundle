<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use ONGR\ElasticsearchBundle\Annotation\Document;
use ONGR\ElasticsearchBundle\Annotation\Id;
use ONGR\ElasticsearchBundle\Annotation\Property;

/**
 * @Document(type="article")
 */
class ArticleOngrDocument
{
    /**
     * @var string
     *
     * @Id
     */
    protected $uuid;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    protected $locale;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    protected $title;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    protected $type;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    protected $changer;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    protected $creator;

    /**
     * @var \DateTime
     *
     * @Property(type="date")
     */
    protected $changed;

    /**
     * @var \DateTime
     *
     * @Property(type="date")
     */
    protected $created;

    /**
     * @var array
     *
     * @Property(type="integer")
     */
    protected $categories;

    /**
     * @var array
     *
     * @Property(type="integer")
     */
    protected $tags;

    /**
     * @param string $uuid
     */
    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    /**
     * Returns uuid.
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set uuid.
     *
     * @param string $uuid
     *
     * @return self
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Returns locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return self
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Returns title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Returns type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Returns changer.
     *
     * @return string
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set changer.
     *
     * @param string $changer
     *
     * @return self
     */
    public function setChanger($changer)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Returns creator.
     *
     * @return string
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set creator.
     *
     * @param string $creator
     *
     * @return self
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Return changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return self
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Returns created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return self
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Returns categories.
     *
     * @return array
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Set categories.
     *
     * @param array $categories
     *
     * @return self
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * Returns tags.
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set tags.
     *
     * @param array $tags
     *
     * @return self
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }
}
