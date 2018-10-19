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
use Doctrine\Common\Collections\Collection;

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
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\CategoryViewObject", multiple=true)
     */
    public $categories;

    /**
     * @var TagViewObject[]|Collection
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\TagViewObject", multiple=true)
     */
    public $tags;

    /**
     * @var MediaViewObject[]|Collection
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\MediaViewObject", multiple=true)
     */
    public $icon;

    /**
     * @var MediaViewObject[]|Collection
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\MediaViewObject", multiple=true)
     */
    public $images;

    public function __construct()
    {
        $this->tags = new Collection();
        $this->categories = new Collection();
        $this->icon = new Collection();
        $this->images = new Collection();
    }
}
