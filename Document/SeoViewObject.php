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
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
     */
    public $title;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
     */
    public $description;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
     */
    public $keywords;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
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
