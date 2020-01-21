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
 * Contains seo information for articles.
 *
 * @ObjectType
 */
class SeoViewObject
{
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
     *        "fields"={
     *            "raw"={"type"="keyword"},
     *            "value"={"type"="text"}
     *        }
     *    }
     * )
     */
    public $description;

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
    public $keywords;

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
    public $canonicalUrl;

    /**
     * @var bool
     *
     * @Property(type="boolean")
     */
    public $noIndex;

    /**
     * @var bool
     *
     * @Property(type="boolean")
     */
    public $noFollow;

    /**
     * @var bool
     *
     * @Property(type="boolean")
     */
    public $hideInSitemap;
}
