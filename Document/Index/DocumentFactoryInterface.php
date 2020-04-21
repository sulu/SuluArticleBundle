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

use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;

/**
 * Interface for document-factory.
 */
interface DocumentFactoryInterface
{
    /**
     * Returns class of article-document.
     */
    public function getClass(string $type): string;

    /**
     * Create a new indexable article-document.
     *
     * @return ArticleViewDocumentInterface
     */
    public function create(string $type);
}
