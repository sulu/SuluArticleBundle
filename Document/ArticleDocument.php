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

use Sulu\Bundle\ArticleBundle\Document\Behavior\DateShardingBehavior;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutableBehavior;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuditableBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocalizedTitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;
use Sulu\Component\DocumentManager\Behavior\VersionBehavior;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;
use Sulu\Component\DocumentManager\Version;

/**
 * Represents an article in phpcr.
 */
class ArticleDocument implements
    UuidBehavior,
    NodeNameBehavior,
    AutoNameBehavior,
    PathBehavior,
    LocalizedTitleBehavior,
    StructureBehavior,
    LocalizedStructureBehavior,
    LocalizedAuditableBehavior,
    DateShardingBehavior,
    RoutableBehavior,
    ExtensionBehavior,
    WorkflowStageBehavior,
    VersionBehavior,
    LocalizedAuthorBehavior,
    ChildrenBehavior,
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
    protected $nodeName;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var object
     */
    protected $parent;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $pageTitle;

    /**
     * @var array
     */
    protected $pages;

    /**
     * @var RouteInterface
     */
    protected $route;

    /**
     * @var string
     */
    protected $routePath;

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
     * @var int
     */
    protected $creator;

    /**
     * @var int
     */
    protected $changer;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var int
     */
    protected $author;

    /**
     * @var \DateTime
     */
    protected $authored;

    /**
     * Document's extensions ie seo, ...
     *
     * @var ExtensionContainer
     */
    protected $extensions;

    /**
     * Workflow Stage currently Test or Published.
     *
     * @var int
     */
    protected $workflowStage;

    /**
     * Is Document is published.
     *
     * @var bool
     */
    protected $published;

    /**
     * List of versions.
     *
     * @var Version[]
     */
    protected $versions = [];

    /**
     * @var ChildrenCollection
     */
    protected $children;

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
        $this->extensions = new ExtensionContainer();
        $this->children = new \ArrayIterator();
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
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeName()
    {
        return $this->nodeName;
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
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent($document)
    {
        $this->parent = $document;
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
    }

    /**
     * Returns route.
     *
     * @return RouteInterface
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
        return get_class($this);
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
    public function setOriginalLocale($locale)
    {
        $this->originalLocale = $locale;
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
    public function getCreator()
    {
        return $this->creator;
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
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * {@inheritdoc}
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Returns identifier.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->getUuid();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionsData()
    {
        return $this->extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtensionsData($extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtension($name, $data)
    {
        $this->extensions[$name] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkflowStage()
    {
        return $this->workflowStage;
    }

    /**
     * {@inheritdoc}
     */
    public function setWorkflowStage($workflowStage)
    {
        $this->workflowStage = $workflowStage;
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
    public function getAuthored()
    {
        return $this->authored;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthored($authored)
    {
        $this->authored = $authored;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * {@inheritdoc}
     */
    public function setVersions($versions)
    {
        $this->versions = $versions;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritdoc}
     */
    public function getArticleUuid()
    {
        return $this->getUuid();
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
    public function getPageNumber()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
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
     * Returns pages.
     *
     * @return array
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Set pages.
     *
     * @param array $pages
     *
     * @return $this
     */
    public function setPages($pages)
    {
        $this->pages = $pages;

        return $this;
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
