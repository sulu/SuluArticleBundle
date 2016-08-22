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
 * Contains the ids and display-options.
 *
 * @Object
 */
class MediaCollectionOngrObject
{
    /**
     * @var int[]
     *
     * @Property(type="integer")
     */
    public $ids = [];

    /**
     * @var string
     *
     * @Property(type="string")
     */
    public $displayOption;

    public function getFirst()
    {
        return reset($this->ids) ?: null;
    }
}
