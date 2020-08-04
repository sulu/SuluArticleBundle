<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index\Factory;

use ONGR\ElasticsearchBundle\Collection\Collection;
use Sulu\Bundle\ArticleBundle\Document\SegmentViewObject;

class SegmentCollectionFactory
{
    public function create(array $segments): Collection
    {
        $collection = new Collection();

        foreach ($segments as $webspaceKey => $segmentKey) {
            if ($segmentKey) {
                $segment = new SegmentViewObject();
                $segment->assignmentKey = $webspaceKey . '#' . $segmentKey;
                $segment->webspaceKey = $webspaceKey;
                $segment->segmentKey = $segmentKey;

                $collection[] = $segment;
            }
        }

        return $collection;
    }
}
