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

use ONGR\ElasticsearchBundle\Annotation\Object;
use ONGR\ElasticsearchBundle\Annotation\Property;

/**
 * Contains page information.
 *
 * @Object
 */
class ArticlePageViewObject
{
    /**
     * @var string
     *
     * @Property(type="string", options={"index"="not_analyzed"})
     */
    public $uuid;

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
     *         "fields":{
     *            "raw":{"type":"string", "index":"not_analyzed"},
     *            "value":{"type":"string"}
     *         }
     *     }
     * )
     */
    public $routePath;

    /**
     * @var int
     *
     * @Property(type="integer", options={"index"="not_analyzed"})
     */
    public $pageNumber;
}
