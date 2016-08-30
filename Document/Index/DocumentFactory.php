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

/**
 * Factory for creating article-documents.
 */
class DocumentFactory implements DocumentFactoryInterface
{
    /**
     * @var string
     */
    private $articleDocumentClass;

    /**
     * @param string $articleDocumentClass
     */
    public function __construct($articleDocumentClass)
    {
        $this->articleDocumentClass = $articleDocumentClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getArticleDocumentClass()
    {
        return $this->articleDocumentClass;
    }

    /**
     * {@inheritdoc}
     */
    public function createArticleDocument()
    {
        return new $this->articleDocumentClass();
    }
}
