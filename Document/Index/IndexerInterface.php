<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;

/**
 * Interface for article-indexer.
 *
 * @method void remove(ArticleDocument $document, ?string $locale)
 */
interface IndexerInterface
{
    /**
     * Clear index.
     */
    public function clear(): void;

    /**
     * Sets state of document to unpublished.
     * Clear published and sets published state to false.
     */
    public function setUnpublished(string $uuid, string $locale): ?ArticleViewDocumentInterface;

    /**
     * Indexes given document.
     */
    public function index(ArticleDocument $document): void;

    /**
     * Removes document from index.
     */
    public function remove(ArticleDocument $document/*, ?string $locale = null*/): void;

    /**
     * Removes a locale for the given document from the index.
     */
    public function removeLocale(ArticleDocument $document, string $locale): void;

    /**
     * Reindexes the document in given locale with the data of the originalLocale.
     */
    public function replaceWithGhostData(ArticleDocument $document, string $locale): void;

    /**
     * Flushes index.
     */
    public function flush(): void;

    /**
     * Drop and recreate elastic-search index.
     */
    public function dropIndex(): void;

    /**
     * Drop and create elastic-search index.
     */
    public function createIndex(): void;
}
