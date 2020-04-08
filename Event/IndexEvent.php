<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Event;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Contains information for index-events.
 */
class IndexEvent extends Event
{
    public const NAME = 'sulu_article.index';

    /**
     * @var ArticleDocument
     */
    private $document;

    /**
     * @var ArticleViewDocumentInterface
     */
    private $viewDocument;

    public function __construct(ArticleDocument $document, ArticleViewDocumentInterface $viewDocument)
    {
        $this->document = $document;
        $this->viewDocument = $viewDocument;
    }

    /**
     * Returns article.
     */
    public function getDocument(): ArticleDocument
    {
        return $this->document;
    }

    /**
     * Returns view-document.
     */
    public function getViewDocument(): ArticleViewDocumentInterface
    {
        return $this->viewDocument;
    }
}
