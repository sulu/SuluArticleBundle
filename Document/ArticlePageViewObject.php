<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use ONGR\ElasticsearchBundle\Annotation\ObjectType;
use ONGR\ElasticsearchBundle\Annotation\Property;

/**
 * Contains page information.
 *
 * @ObjectType
 */
class ArticlePageViewObject
{
    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    public $uuid;

    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *        "fields"={
     *            "raw"={"type"="keyword"},
     *            "value"={"type"="text"}
     *        }
     *    }
     * )
     */
    public $title;

    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"},
     *            "value":{"type":"text"}
     *         }
     *     }
     * )
     */
    public $routePath;

    /**
     * @var int
     *
     * @Property(type="integer")
     */
    public $pageNumber;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    public $contentData;

    /**
     * @var \ArrayObject
     */
    public $content;

    /**
     * @var \ArrayObject
     */
    public $view;
}
