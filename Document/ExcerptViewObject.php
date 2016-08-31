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

use ONGR\ElasticsearchBundle\Annotation\Embedded;
use ONGR\ElasticsearchBundle\Annotation\Object;
use ONGR\ElasticsearchBundle\Annotation\Property;
use ONGR\ElasticsearchBundle\Collection\Collection;

/**
 * Contains excerpt information for articles.
 *
 * @Object
 */
class ExcerptViewObject
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
    public $more;

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
     * @var CategoryViewObject[]|Collection
     *
     * @Embedded(class="SuluArticleBundle:CategoryViewObject", multiple=true)
     */
    public $categories;

    /**
     * @var TagViewObject[]|Collection
     *
     * @Embedded(class="SuluArticleBundle:TagViewObject", multiple=true)
     */
    public $tags;

    /**
     * @var MediaCollectionViewObject|MediaViewObject[]
     *
     * @Embedded(class="SuluArticleBundle:MediaCollectionViewObject")
     */
    public $icon;

    /**
     * @var MediaCollectionViewObject|MediaViewObject[]
     *
     * @Embedded(class="SuluArticleBundle:MediaCollectionViewObject")
     */
    public $images;

    public function __construct()
    {
        $this->tags = new Collection();
        $this->categories = new Collection();
    }
}
