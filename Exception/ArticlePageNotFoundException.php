<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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

    /**
     * @param string $pageUuid
     * @param string $articleUuid
     */
    public function __construct($pageUuid, $articleUuid)
    {
        parent::__construct(sprintf('Page "%s" not found in article "%s".', $pageUuid, $articleUuid));
        $this->pageUuid = $pageUuid;
        $this->articleUuid = $articleUuid;
    }

    /**
     * Returns page-uuid.
     *
     * @return string
     */
    public function getPageUuid()
    {
        return $this->pageUuid;
    }

    /**
     * Returns article-uuid.
     *
     * @return string
     */
    public function getArticleUuid()
    {
        return $this->articleUuid;
    }
}
