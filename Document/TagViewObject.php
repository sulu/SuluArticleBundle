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
 * Contains excerpt information for articles.
 *
 * @ObjectType
 */
class TagViewObject
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
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
     */
    public $name;
}
