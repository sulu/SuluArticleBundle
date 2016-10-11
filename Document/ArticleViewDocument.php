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
class ArticleViewDocument implements ArticleViewDocumentInterface
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
     * @Property(
     *     type="string",
     *     options={
     *         "fields":{
     *            "raw":{"type":"string", "index":"not_analyzed"},
     *            "value":{"type":"string"}
     *         }
     *     }
     * )
     */
    protected $title;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *         "fields":{
     *            "raw":{"type":"string", "index":"not_analyzed"},
     *            "value":{"type":"string"}
     *         }
     *     }
     * )
     */
    protected $routePath;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *         "analyzer":"keyword"
     *     }
     * )
     */
    protected $type;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *         "analyzer":"keyword"
     *     }
     * )
     */
    protected $structureType;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *         "fields":{
     *            "raw":{"type":"string", "index":"not_analyzed"},
     *            "value":{"type":"string"}
     *         }
     *     }
     * )
     */
    protected $changerFullName;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *         "fields":{
     *            "raw":{"type":"string", "index":"not_analyzed"},
     *            "value":{"type":"string"}
     *         }
     *     }
     * )
     */
    protected $creatorFullName;

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
     * @var ExcerptViewObject
     *
     * @Embedded(class="SuluArticleBundle:ExcerptViewObject")
     */
    protected $excerpt;

    /**
     * @var SeoViewObject
     *
     * @Embedded(class="SuluArticleBundle:SeoViewObject")
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
     * @var \DateTime
     *
     * @Property(type="date")
     */
    protected $published;

    /**
     * @var bool
     *
     * @Property(type="boolean")
     */
    protected $publishedState;

    /**
     * @param string $uuid
     */
    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutePath()
    {
        return $this->routePath;
    }

    /**
     * {@inheritdoc}
     */
    public function setRoutePath($routePath)
    {
        $this->routePath = $routePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStructureType()
    {
        return $this->structureType;
    }

    /**
     * {@inheritdoc}
     */
    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChangerFullName()
    {
        return $this->changerFullName;
    }

    /**
     * {@inheritdoc}
     */
    public function setChangerFullName($changerFullName)
    {
        $this->changerFullName = $changerFullName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatorFullName()
    {
        return $this->creatorFullName;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatorFullName($creatorFullName)
    {
        $this->creatorFullName = $creatorFullName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * {@inheritdoc}
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExcerpt()
    {
        return $this->excerpt;
    }

    /**
     * {@inheritdoc}
     */
    public function setExcerpt(ExcerptViewObject $excerpt)
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSeo()
    {
        return $this->seo;
    }

    /**
     * {@inheritdoc}
     */
    public function setSeo($seo)
    {
        $this->seo = $seo;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthored()
    {
        return $this->authored;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthored(\DateTime $authored = null)
    {
        $this->authored = $authored;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthors($authors)
    {
        $this->authors = $authors;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTeaserDescription()
    {
        return $this->teaserDescription;
    }

    /**
     * {@inheritdoc}
     */
    public function setTeaserDescription($teaserDescription)
    {
        $this->teaserDescription = $teaserDescription;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTeaserMediaId()
    {
        return $this->teaserMediaId;
    }

    /**
     * {@inheritdoc}
     */
    public function setTeaserMediaId($teaserMediaId)
    {
        $this->teaserMediaId = $teaserMediaId;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * {@inheritdoc}
     */
    public function setPublished(\DateTime $published = null)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublishedState()
    {
        return $this->publishedState;
    }

    /**
     * {@inheritdoc}
     */
    public function setPublishedState($publishedState)
    {
        $this->publishedState = $publishedState;

        return $this;
    }
}
