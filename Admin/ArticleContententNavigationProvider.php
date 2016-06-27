<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

/**
 * Provides tabs for article-form.
 */
class ArticleContententNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        $details = new ContentNavigationItem('sulu_article.edit.details');
        $details->setAction('details');
        $details->setPosition(10);
        $details->setComponent('articles/edit/details@suluarticle');

        return [$details];
    }
}
