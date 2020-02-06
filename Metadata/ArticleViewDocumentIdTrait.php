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

/**
 * Encapsulates function to get the correct id for an article view document.
 */
trait ArticleViewDocumentIdTrait
{
    /**
     * @param string $uuid
     * @param string $locale
     *
     * @return string
     */
    protected function getViewDocumentId($uuid, $locale)
    {
        return $uuid . '-' . $locale;
    }

    /**
     * @param array $uuids
     * @param string $locale
     *
     * @return array
     */
    protected function getViewDocumentIds($uuids, $locale)
    {
        $ids = [];

        foreach ($uuids as $uuid) {
            $ids[] = $this->getViewDocumentId($uuid, $locale);
        }

        return $ids;
    }
}
