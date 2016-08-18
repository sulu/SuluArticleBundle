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
use ONGR\ElasticsearchBundle\Annotation\Embedded;
use ONGR\ElasticsearchBundle\Annotation\Id;
use ONGR\ElasticsearchBundle\Annotation\Property;

/**
 * Indexable document for articles.
 *
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
    protected $routePath;

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
     * @var ExcerptOngrObject
     *
     * @Embedded(class="SuluArticleBundle:ExcerptOngrObject")
     */
    protected $excerpt;

    /**
     * @var SeoOngrObject
     *
     * @Embedded(class="SuluArticleBundle:SeoOngrObject")
     */
    protected $seo;

    /**
     * @var \DateTime
     *
     * @Property(type="date")
     */
    protected $authored;

    /**
     * @var int[]
     *
     * @Property(type="integer")
     */
    protected $authors;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    protected $teaserDescription = '';

    /**
     * @var int
     *
     * @Property(type="integer")
     */
    protected $teaserMediaId;

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
     * Returns route-path.
     *
     * @return string
     */
    public function getRoutePath()
    {
        return $this->routePath;
    }

    /**
     * Set route-path.
     *
     * @param string $routePath
     */
    public function setRoutePath($routePath)
    {
        $this->routePath = $routePath;
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
     * Returns excerpt.
     *
     * @return ExcerptOngrObject
     */
    public function getExcerpt()
    {
        return $this->excerpt;
    }

    /**
     * Set excerpt.
     *
     * @param ExcerptOngrObject $excerpt
     *
     * @return self
     */
    public function setExcerpt(ExcerptOngrObject $excerpt)
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    /**
     * Returns seo.
     *
     * @return SeoOngrObject
     */
    public function getSeo()
    {
        return $this->seo;
    }

    /**
     * Set seo.
     *
     * @param SeoOngrObject $seo
     *
     * @return self
     */
    public function setSeo($seo)
    {
        $this->seo = $seo;

        return $this;
    }

    /**
     * Returns authored.
     *
     * @return \DateTime
     */
    public function getAuthored()
    {
        return $this->authored;
    }

    /**
     * Set authored date.
     *
     * @param \DateTime $authored
     *
     * @return $this
     */
    public function setAuthored(\DateTime $authored = null)
    {
        $this->authored = $authored;

        return $this;
    }

    /**
     * Returns authors.
     *
     * @return int[]
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * Set authors.
     *
     * @param int[] $authors
     *
     * @return $this
     */
    public function setAuthors($authors)
    {
        $this->authors = $authors;

        return $this;
    }

    /**
     * Returns teaser-description.
     *
     * @return string
     */
    public function getTeaserDescription()
    {
        return $this->teaserDescription;
    }

    /**
     * Set teaser-description.
     *
     * @param string $teaserDescription
     *
     * @return $this
     */
    public function setTeaserDescription($teaserDescription)
    {
        $this->teaserDescription = $teaserDescription;

        return $this;
    }

    /**
     * Returns teaser-media-id.
     *
     * @return int
     */
    public function getTeaserMediaId()
    {
        return $this->teaserMediaId;
    }

    /**
     * Set teaser-media-id.
     *
     * @param int $teaserMediaId
     *
     * @return int
     */
    public function setTeaserMediaId($teaserMediaId)
    {
        $this->teaserMediaId = $teaserMediaId;
    }
}
