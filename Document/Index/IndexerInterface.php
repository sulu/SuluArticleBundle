<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;

/**
 * Interface for article-indexer.
 */
interface IndexerInterface
{
    /**
     * Clear index.
     */
    public function clear();

    /**
     * Sets state of document to unpublished.
     * Clear published and sets published state to false.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return ArticleDocument $document
     */
    public function setUnpublished($uuid, $locale);

    /**
     * Indexes given document.
     *
     * @param ArticleDocument $document
     */
    public function index(ArticleDocument $document);

    /**
     * Removes document from index.
     *
     * @param ArticleDocument $document
     */
    public function remove($document);

    /**
     * Flushes index.
     */
    public function flush();

    /**
     * Drop and recreate elastic-search index.
     */
    public function dropIndex();

    /**
     * Drop and create elastic-search index.
     */
    public function createIndex();
}
