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
class ArticleOngrDocument implements ArticleOngrDocumentInterface
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
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
     */
    protected $title;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
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
     * @Property(
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
     */
    protected $structureType;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
     */
    protected $changer;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
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
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * {@inheritdoc}
     */
    public function setChanger($changer)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;

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
    public function setExcerpt(ExcerptOngrObject $excerpt)
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
}
