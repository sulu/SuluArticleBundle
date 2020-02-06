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

/**
 * Factory for creating article-documents.
 */
class DocumentFactory implements DocumentFactoryInterface
{
    /**
     * @var array
     */
    private $documents;

    /**
     * @param array $documents
     */
    public function __construct($documents)
    {
        $this->documents = $documents;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass($type)
    {
        return $this->documents[$type]['view'];
    }

    /**
     * {@inheritdoc}
     */
    public function create($type)
    {
        $class = $this->getClass($type);

        return new $class();
    }
}
