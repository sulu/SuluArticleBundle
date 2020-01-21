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
 * Contains excerpt information for articles.
 *
 * @ObjectType
 */
class CategoryViewObject
{
    /**
     * @var int
     *
     * @Property(type="integer")
     */
    public $id;

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
    public $name;

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
    public $key;

    /**
     * @var string[]
     *
     * @Property(type="text")
     */
    public $keywords = [];
}
