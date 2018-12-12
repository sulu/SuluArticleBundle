<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\DependencyInjection;

use Sulu\Bundle\ArticleBundle\Document\ArticlePageViewObject;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Initializes configuration tree for article-bundle.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('sulu_article')
            ->children()
                ->arrayNode('default_main_webspace')
                    ->useAttributeAsKey('locale')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return ['default' => $v];
                        })
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('default_additional_webspaces')
                    ->beforeNormalization()
                        ->ifTrue(function ($v) {
                            return count(array_filter(array_keys($v), 'is_string')) <= 0;
                        })
                        ->then(function ($v) {
                            return ['default' => $v];
                        })
                    ->end()
                    ->prototype('array')->useAttributeAsKey('locale')->prototype('scalar')->end()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('smart_content')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('default_limit')->defaultValue(100)->end()
                    ->end()
                ->end()
                ->arrayNode('content_types')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('article')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluArticleBundle:Template:content-types/article-selection.html.twig')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('page_tree_route')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluArticleBundle:Template:content-types/page-tree-route.html.twig')
                                ->end()
                                ->enumNode('page_route_cascade')
                                    ->values(['request', 'task', 'off'])
                                    ->defaultValue('request')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('documents')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('article')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('view')->defaultValue(ArticleViewDocument::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('article_page')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('view')->defaultValue(ArticlePageViewObject::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('types')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('translation_key')->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('display_tab_all')->defaultTrue()->info('Display tab \'all\' in list view')->end()
                ->scalarNode('default_author')->defaultTrue()->info('Set default author if none isset')->end()
                ->arrayNode('search_fields')
                    ->prototype('scalar')->end()->defaultValue([
                        'title',
                        'excerpt.title',
                        'excerpt.description',
                        'excerpt.seo.title',
                        'excerpt.seo.description',
                        'excerpt.seo.keywords',
                        'teaser_description',
                    ])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
