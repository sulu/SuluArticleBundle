<?php

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
class SegmentViewObject
{
    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    public $assignmentKey;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    public $webspaceKey;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    public $segmentKey;
}
