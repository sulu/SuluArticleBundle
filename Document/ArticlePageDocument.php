<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use Sulu\Bundle\ArticleBundle\Document\Behavior\PageBehavior;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutablePageBehavior;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
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
    LocalizedStructureBehavior,
    RoutablePageBehavior,
    PageBehavior,
    ArticleInterface,
    ShadowLocaleBehavior
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $pageTitle;

    /**
     * @var ArticleDocument
     */
    protected $parent;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $originalLocale;

    /**
     * @var string
     */
    protected $structureType;

    /**
     * @var StructureInterface
     */
    protected $structure;

    /**
     * @var RouteInterface
     */
    protected $route;

    /**
     * @var string
     */
    protected $routePath;

    /**
     * @var int
     */
    protected $pageNumber;

    /**
     * Shadow locale is enabled.
     *
     * @var bool
     */
    protected $shadowLocaleEnabled = false;

    /**
     * Shadow locale.
     *
     * @var string
     */
    protected $shadowLocale;

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
    public function setUuid($uuid): RoutablePageBehavior
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
    public function getPageTitle(): ?string
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
    public function getStructureType(): ?string
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
    public function getStructure(): StructureInterface
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
    public function removeRoute(): RoutablePageBehavior
    {
        $this->route = null;
        $this->routePath = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutePath(): ?string
    {
        return $this->routePath;
    }

    /**
     * {@inheritdoc}
     */
    public function setRoutePath($routePath): RoutablePageBehavior
    {
        $this->routePath = $routePath;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return get_class($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    /**
     * {@inheritdoc}
     */
    public function setPageNumber(int $pageNumber): PageBehavior
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArticleUuid(): string
    {
        return $this->getParent()->getUuid();
    }

    /**
     * {@inheritdoc}
     */
    public function getPageUuid(): string
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

    /**
     * {@inheritdoc}
     */
    public function getShadowLocale()
    {
        return $this->shadowLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function setShadowLocale($shadowLocale)
    {
        $this->shadowLocale = $shadowLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function isShadowLocaleEnabled()
    {
        return $this->shadowLocaleEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function setShadowLocaleEnabled($shadowLocaleEnabled)
    {
        $this->shadowLocaleEnabled = $shadowLocaleEnabled;
    }
}
