<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Exception;

/**
 * Thrown when article-page not found in article.
 */
class ArticlePageNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $pageUuid;

    /**
     * @var string
     */
    private $articleUuid;

    public function __construct(string $pageUuid, string $articleUuid)
    {
        parent::__construct(\sprintf('Page "%s" not found in article "%s".', $pageUuid, $articleUuid));
        $this->pageUuid = $pageUuid;
        $this->articleUuid = $articleUuid;
    }

    /**
     * Returns page-uuid.
     */
    public function getPageUuid(): string
    {
        return $this->pageUuid;
    }

    /**
     * Returns article-uuid.
     */
    public function getArticleUuid(): string
    {
        return $this->articleUuid;
    }
}
