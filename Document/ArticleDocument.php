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
use Sulu\Component\Content\Document\Behavior\LocalizedAuditableBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
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
    DateShardingBehavior
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
}
