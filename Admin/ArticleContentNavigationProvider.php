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
        $details = new ContentNavigationItem('sulu_article.edit.details');

        $action = 'details';
        if (array_key_exists('page', $options) && (int) $options['page'] !== 1) {
            $action = 'page:' . $options['page'] . '/' . $action;
        }

        $details->setAction($action);
        $details->setPosition(10);
        $details->setComponent('articles/edit/details@suluarticle');

        $seo = new ContentNavigationItem('content-navigation.contents.seo');
        $seo->setId('seo');
        $seo->setPosition(20);
        $seo->setAction('seo');
        $seo->setComponent('articles/edit/seo@suluarticle');
        $seo->setDisplay(['edit']);
        $seo->setDisplayConditions(
            [
                new DisplayCondition('type', DisplayCondition::OPERATOR_EQUAL, null),
            ]
        );

        $excerpt = new ContentNavigationItem('content-navigation.contents.excerpt');
        $excerpt->setId('excerpt');
        $excerpt->setPosition(30);
        $excerpt->setAction('excerpt');
        $excerpt->setComponent('articles/edit/excerpt@suluarticle');
        $excerpt->setDisplay(['edit']);
        $excerpt->setDisplayConditions(
            [
                new DisplayCondition('type', DisplayCondition::OPERATOR_EQUAL, null),
            ]
        );

        $settings = new ContentNavigationItem('content-navigation.contents.settings');
        $settings->setId('settings');
        $settings->setPosition(40);
        $settings->setAction('settings');
        $settings->setComponent('articles/edit/settings@suluarticle');
        $settings->setDisplay(['edit']);
        $settings->setDisplayConditions(
            [
                new DisplayCondition('type', DisplayCondition::OPERATOR_EQUAL, null),
            ]
        );

        return [$details, $seo, $excerpt, $settings];
    }
}
