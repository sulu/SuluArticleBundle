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

    public function __construct(array $documents)
    {
        $this->documents = $documents;
    }

    public function getClass(string $type): string
    {
        return $this->documents[$type]['view'];
    }

    public function create(string $type)
    {
        $class = $this->getClass($type);

        return new $class();
    }
}
