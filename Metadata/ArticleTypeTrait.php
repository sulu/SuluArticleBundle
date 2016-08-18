<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Metadata;

use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * Encapsulates function to extract the type from structure-metadata.
 */
trait ArticleTypeTrait
{
    /**
     * Returns type for given structure-metadata.
     *
     * @param StructureMetadata $metadata
     *
     * @return string
     */
    protected function getType(StructureMetadata $metadata)
    {
        if (!$metadata->hasTag(ArticleAdmin::STRUCTURE_TAG_TYPE)) {
            return 'default';
        }

        $tag = $metadata->getTag(ArticleAdmin::STRUCTURE_TAG_TYPE);
        if (!array_key_exists('type', $tag['attributes'])) {
            return 'default';
        }

        return $tag['attributes']['type'];
    }
}
