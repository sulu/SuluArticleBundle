<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Content;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * Represents a data-item for smart-content data-provider.
 *
 * @ExclusionPolicy("all")
 */
class ArticleDataItem implements ItemInterface
{
    /**
     * @var string
     *
     * @Expose
     */
    private $id;

    /**
     * @var string
     *
     * @Expose
     */
    private $title;

    /**
     * @var ArticleViewDocumentInterface
     */
    private $resource;

    /**
     * @param string $id
     * @param string $title
     * @param ArticleViewDocumentInterface $resource
     */
    public function __construct($id, $title, ArticleViewDocumentInterface $resource)
    {
        $this->id = $id;
        $this->title = $title;
        $this->resource = $resource;
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getImage()
    {
        return;
    }
}
