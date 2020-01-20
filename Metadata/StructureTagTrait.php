<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Metadata;

use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * Encapsulates function to extract configuration from structure-metadata.
 */
trait StructureTagTrait
{
    /**
     * Returns type for given structure-metadata.
     */
    protected function getType(StructureMetadata $metadata): string
    {
        return $this->getTagAttribute($metadata, ArticleAdmin::STRUCTURE_TAG_TYPE, 'type', 'default');
    }

    /**
     * Returns multipage-configuration for given structure-metadata.
     */
    protected function getMultipage(StructureMetadata $metadata): string
    {
        return $this->getTagAttribute($metadata, ArticleAdmin::STRUCTURE_TAG_MULTIPAGE, 'enabled', false);
    }

    /**
     * Returns attribute for given tag in metadata.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    private function getTagAttribute(StructureMetadata $metadata, string $tag, string $attribute, $default)
    {
        if (!$metadata->hasTag($tag)) {
            return $default;
        }

        $tag = $metadata->getTag($tag);
        if (!array_key_exists($attribute, $tag['attributes'])) {
            return $default;
        }

        return $tag['attributes'][$attribute];
    }
}
