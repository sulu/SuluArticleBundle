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
use ONGR\ElasticsearchBundle\Collection\Collection;

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
    protected $id;

    /**
     * @var string
     *
     * @Property(type="string", options={"analyzer": "keyword"})
     */
    protected $uuid;

    /**
     * @var string
     *
     * @Property(type="string", options={"analyzer": "keyword"})
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
     *         "analyzer": "pathAnalyzer",
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
     * @Property(type="string", options={"analyzer": "keyword"})
     */
    protected $parentPageUuid;

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
    protected $typeTranslation;

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
    protected $authorFullName;

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
     * @var LocalizationStateViewObject
     *
     * @Embedded(class="SuluArticleBundle:LocalizationStateViewObject")
     */
    protected $localizationState;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    protected $authorId;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    protected $creatorContactId;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    protected $changerContactId;

    /**
     * @var ArticlePageViewObject[]
     *
     * @Embedded(class="SuluArticleBundle:ArticlePageViewObject", multiple=true)
     */
    protected $pages = [];

    /**
     * @var string
     *
     * @Property(type="binary")
     */
    protected $contentData;

    /**
     * @var \ArrayObject
     */
    protected $content;

    /**
     * @var \ArrayObject
     */
    protected $view;

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
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
    public function getParentPageUuid()
    {
        return $this->parentPageUuid;
    }

    /**
     * {@inheritdoc}
     */
    public function setParentPageUuid($parentPageUuid)
    {
        $this->parentPageUuid = $parentPageUuid;

        return $this;
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
    public function getTypeTranslation()
    {
        return $this->typeTranslation;
    }

    /**
     * {@inheritdoc}
     */
    public function setTypeTranslation($typeTranslation)
    {
        $this->typeTranslation = $typeTranslation;

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
    public function setSeo(SeoViewObject $seo)
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
    public function getAuthorFullName()
    {
        return $this->authorFullName;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthorFullName($authorFullName)
    {
        $this->authorFullName = $authorFullName;

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

    /**
     * {@inheritdoc}
     */
    public function getLocalizationState()
    {
        return $this->localizationState;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocalizationState(LocalizationStateViewObject $localizationState)
    {
        $this->localizationState = $localizationState;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthorId($authorId)
    {
        $this->authorId = $authorId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatorContactId($creatorContactId)
    {
        $this->creatorContactId = $creatorContactId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatorContactId()
    {
        return $this->creatorContactId;
    }

    /**
     * {@inheritdoc}
     */
    public function setChangerContactId($changerContactId)
    {
        $this->changerContactId = $changerContactId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChangerContactId()
    {
        return $this->changerContactId;
    }

    /**
     * {@inheritdoc}
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * {@inheritdoc}
     */
    public function setPages(Collection $pages)
    {
        $this->pages = $pages;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData()
    {
        return $this->contentData;
    }

    /**
     * {@inheritdoc}
     */
    public function setContentData($contentData)
    {
        $this->contentData = $contentData;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function setContent(\ArrayObject $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * {@inheritdoc}
     */
    public function setView(\ArrayObject $view)
    {
        $this->view = $view;

        return $this;
    }
}
