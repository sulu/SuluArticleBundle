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

/**
 * Container for article-events.
 */
final class Events
{
    /**
     * Indicates the index-event for articles.
     */
    const INDEX_EVENT = 'sulu_article.index';

    /**
     * Private constructor.
     */
    public function __construct()
    {
    }
}
