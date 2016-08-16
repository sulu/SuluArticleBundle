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
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuditableBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocalizedTitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;

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
    RoutableInterface,
    ExtensionBehavior,
    WorkflowStageBehavior
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $nodeName;

    /**
     * @var string
     */
    private $path;

    /**
     * @var object
     */
    private $parent;

    /**
     * @var string
     */
    private $title;

    /**
     * @var RouteInterface
     */
    private $route;

    /**
     * @var string
     */
    private $routePath;

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
     * @var int
     */
    private $creator;

    /**
     * @var int
     */
    private $changer;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

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
     * Timestamp of authoring (can be set by user).
     *
     * @var \DateTime
     */
    protected $authored;

    public function __construct()
    {
        $this->structure = new Structure();
        $this->extensions = new ExtensionContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set uuid.
     *
     * @param string $uuid
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
     * Set route.
     *
     * @param RouteInterface $route
     */
    public function setRoute(RouteInterface $route)
    {
        $this->route = $route;
        $this->routePath = $route->getPath();
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
     * Set authored date-time.
     *
     * @param \DateTime $authored
     *
     * @return $this
     */
    public function setAuthored($authored)
    {
        $this->authored = $authored;

        return $this;
    }

    /**
     * Returns date-time of authoring this article.
     *
     * @return \DateTime
     */
    public function getAuthored()
    {
        return $this->authored;
    }
}
