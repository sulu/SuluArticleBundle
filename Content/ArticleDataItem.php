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

use Sulu\Bundle\ArticleBundle\Document\ArticleOngrDocumentInterface;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * Represents a data-item for smart-content data-provider.
 */
class ArticleDataItem implements ItemInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var ArticleOngrDocumentInterface
     */
    private $resource;

    /**
     * @param string $id
     * @param string $title
     * @param ArticleOngrDocumentInterface $resource
     */
    public function __construct($id, $title, ArticleOngrDocumentInterface $resource)
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
