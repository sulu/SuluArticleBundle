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
                ->scalarNode('display_tab_all')->defaultTrue()->info("Display tab 'all' in list view")->end()
            ->end();

        return $treeBuilder;
    }
}
