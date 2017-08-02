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
use Sulu\Bundle\AdminBundle\Navigation\DisplayCondition;

/**
 * Provides tabs for article-form.
 */
class ArticleContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        $action = 'details';
        $page = 1;
        if (array_key_exists('page', $options) && (int) $options['page'] !== 1) {
            $action = 'page:' . $options['page'] . '/' . $action;
            $page = (int) $options['page'];
        }

        $tabs = [];

        $tabs['details'] = new ContentNavigationItem('sulu_article.edit.details');
        $tabs['details']->setAction($action);
        $tabs['details']->setPosition(10);
        $tabs['details']->setComponent('articles/edit/details@suluarticle');

        if ($page < 2) {
            $tabs['seo'] = new ContentNavigationItem('content-navigation.contents.seo');
            $tabs['seo']->setId('seo');
            $tabs['seo']->setPosition(20);
            $tabs['seo']->setAction('seo');
            $tabs['seo']->setComponent('articles/edit/seo@suluarticle');
            $tabs['seo']->setDisplay(['edit']);
            $tabs['seo']->setDisplayConditions(
                [
                    new DisplayCondition('type', DisplayCondition::OPERATOR_EQUAL, null),
                ]
            );

            $tabs['excerpt'] = new ContentNavigationItem('content-navigation.contents.excerpt');
            $tabs['excerpt']->setId('excerpt');
            $tabs['excerpt']->setPosition(30);
            $tabs['excerpt']->setAction('excerpt');
            $tabs['excerpt']->setComponent('articles/edit/excerpt@suluarticle');
            $tabs['excerpt']->setDisplay(['edit']);
            $tabs['excerpt']->setDisplayConditions(
                [
                    new DisplayCondition('type', DisplayCondition::OPERATOR_EQUAL, null),
                ]
            );

            $tabs['settings'] = new ContentNavigationItem('content-navigation.contents.settings');
            $tabs['settings']->setId('settings');
            $tabs['settings']->setPosition(40);
            $tabs['settings']->setAction('settings');
            $tabs['settings']->setComponent('articles/edit/settings@suluarticle');
            $tabs['settings']->setDisplay(['edit']);
            $tabs['settings']->setDisplayConditions(
                [
                    new DisplayCondition('type', DisplayCondition::OPERATOR_EQUAL, null),
                ]
            );
        }

        return $tabs;
    }
}
