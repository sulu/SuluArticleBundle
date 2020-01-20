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
    protected function getViewDocumentId(string $uuid, string $locale): string
    {
        return $uuid . '-' . $locale;
    }

    protected function getViewDocumentIds(array $uuids, string $locale): array
    {
        $ids = [];

        foreach ($uuids as $uuid) {
            $ids[] = $this->getViewDocumentId($uuid, $locale);
        }

        return $ids;
    }
}
