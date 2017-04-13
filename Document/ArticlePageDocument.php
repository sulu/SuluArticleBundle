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

use Sulu\Bundle\ArticleBundle\Document\Behavior\PageBehavior;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutablePageBehavior;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocalizedTitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;

/**
 * Represents an article-page in phpcr.
 */
class ArticlePageDocument implements
    UuidBehavior,
    LocalizedTitleBehavior,
    ParentBehavior,
    AutoNameBehavior,
    PathBehavior,
    StructureBehavior,
    RoutablePageBehavior,
    PageBehavior,
    ArticleInterface
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $pageTitle;

    /**
     * @var ArticleDocument
     */
    private $parent;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $originalLocale;

    /**
     * @var string
     */
    private $structureType;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var RouteInterface
     */
    private $route;

    /**
     * @var string
     */
    private $routePath;

    /**
     * @var int
     */
    private $pageNumber;

    public function __construct()
    {
        $this->structure = new Structure();
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
     * Returns pageTitle.
     *
     * @return string
     */
    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    /**
     * Set pageTitle.
     *
     * @param string $pageTitle
     *
     * @return $this
     */
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
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
    public function getOriginalLocale()
    {
        return $this->originalLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function setOriginalLocale($originalLocale)
    {
        $this->originalLocale = $originalLocale;

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
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * {@inheritdoc}
     */
    public function setRoute(RouteInterface $route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRoute()
    {
        $this->route = null;
        $this->routePath = null;
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
    public function getClass()
    {
        return self::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * {@inheritdoc}
     */
    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArticleUuid()
    {
        return $this->getParent()->getUuid();
    }

    /**
     * {@inheritdoc}
     */
    public function getPageUuid()
    {
        return $this->getUuid();
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkflowStage()
    {
        return $this->getParent()->getWorkflowStage();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionsData()
    {
        return $this->getParent()->getExtensionsData();
    }
}
