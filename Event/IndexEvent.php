<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Event;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleOngrDocumentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Contains information for index-events.
 */
class IndexEvent extends Event
{
    /**
     * @var ArticleDocument
     */
    private $article;

    /**
     * @var ArticleOngrDocumentInterface
     */
    private $ongrDocument;

    /**
     * @param ArticleDocument $article
     * @param ArticleOngrDocumentInterface $ongrDocument
     */
    public function __construct(ArticleDocument $article, ArticleOngrDocumentInterface $ongrDocument)
    {
        $this->article = $article;
        $this->ongrDocument = $ongrDocument;
    }

    /**
     * Returns article.
     *
     * @return ArticleDocument
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * Returns ongr-document.
     *
     * @return ArticleOngrDocumentInterface
     */
    public function getOngrDocument()
    {
        return $this->ongrDocument;
    }
}
