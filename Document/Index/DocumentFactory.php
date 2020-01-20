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
 * Factory for creating article-documents.
 */
class DocumentFactory implements DocumentFactoryInterface
{
    /**
     * @var array
     */
    private $documents;

    public function __construct(array $documents)
    {
        $this->documents = $documents;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(string $type): string
    {
        return $this->documents[$type]['view'];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $type): ArticleViewDocumentInterface
    {
        $class = $this->getClass($type);

        return new $class();
    }
}
