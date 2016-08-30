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

use Sulu\Bundle\ArticleBundle\Document\ArticleOngrDocumentInterface;

/**
 * Interface for document-factory.
 */
interface DocumentFactoryInterface
{
    /**
     * Returns class of article-document.
     *
     * @return string
     */
    public function getArticleDocumentClass();

    /**
     * Create a new indexable article-document.
     *
     * @return ArticleOngrDocumentInterface
     */
    public function createArticleDocument();
}
