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

/**
 * Contains excerpt information for articles.
 *
 * @Object
 */
class ExcerptOngrObject
{
    /**
     * @var string
     *
     * @Property(type="string")
     */
    public $title;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    public $more;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    public $description;

    /**
     * @var int[]
     *
     * @Property(type="integer")
     */
    public $categories;

    /**
     * @var int[]
     *
     * @Property(type="integer")
     */
    public $tags;

    /**
     * @var MediaCollectionOngrObject
     *
     * @Embedded(class="SuluArticleBundle:MediaCollectionOngrObject")
     */
    public $icon;

    /**
     * @var MediaCollectionOngrObject
     *
     * @Embedded(class="SuluArticleBundle:MediaCollectionOngrObject")
     */
    public $images;
}
