<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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

    public function __construct(string $id, string $title, ArticleViewDocumentInterface $resource)
    {
        $this->id = $id;
        $this->title = $title;
        $this->resource = $resource;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getImage()
    {
        return;
    }
}
