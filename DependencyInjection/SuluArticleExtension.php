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

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Structure\ArticleBridge;
use Sulu\Bundle\ArticleBundle\Document\Structure\ArticlePageBridge;
use Sulu\Bundle\ArticleBundle\Exception\ArticlePageNotFoundException;
use Sulu\Bundle\ArticleBundle\Exception\ParameterNotAllowedException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages article bundle configuration.
 */
class SuluArticleExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_core')) {
            $container->prependExtensionConfig(
                'sulu_core',
                [
                    'content' => [
                        'structure' => [
                            'type_map' => [
                                'article' => ArticleBridge::class,
                                'article_page' => ArticlePageBridge::class,
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig(
                'jms_serializer',
                [
                    'metadata' => [
                        'directories' => [
                            [
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Bundle\ArticleBundle',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_search')) {
            $container->prependExtensionConfig(
                'sulu_content',
                [
                    'search' => [
                        'mapping' => [
                            ArticleDocument::class => ['index' => 'article'],
                            ArticlePageDocument::class => ['index' => 'article_page'],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_document_manager')) {
            $container->prependExtensionConfig(
                'sulu_document_manager',
                [
                    'mapping' => [
                        'article' => ['class' => ArticleDocument::class, 'phpcr_type' => 'sulu:article'],
                        'article_page' => ['class' => ArticlePageDocument::class, 'phpcr_type' => 'sulu:articlepage'],
                    ],
                    'path_segments' => [
                        'articles' => 'articles',
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_route')) {
            $container->prependExtensionConfig(
                'sulu_route',
                [
                    'mappings' => [
                        ArticlePageDocument::class => [
                            'generator' => 'article_page',
                            'options' => [
                                'route_schema' => '/{translator.trans("page")}-{object.getPageNumber()}',
                                'parent' => '{object.getParent().getRoutePath()}',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            ParameterNotAllowedException::class => 400,
                            ArticlePageNotFoundException::class => 404,
                        ],
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('sulu_article.types', $config['types']);
        $container->setParameter('sulu_article.documents', $config['documents']);
        $container->setParameter('sulu_article.view_document.article.class', $config['documents']['article']['view']);
        $container->setParameter('sulu_article.display_tab_all', $config['display_tab_all']);

        $container->setParameter(
            'sulu_article.content-type.article.template',
            $config['content_types']['article']['template']
        );

        $container->setParameter(
            'sulu_article.content-type.page_tree_route.template',
            $config['content_types']['page_tree_route']['template']
        );

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $bundles = $container->getParameter('kernel.bundles');
        if (array_key_exists('SuluAutomationBundle', $bundles)) {
            $loader->load('automation.xml');
        }

        $container->setAlias(
            'sulu_article.page_tree_route.updater',
            'sulu_article.page_tree_route.updater.' . $config['content_types']['page_tree_route']['page_route_cascade']
        );

        if ($config['content_types']['page_tree_route']['page_route_cascade'] !== 'off') {
            $loader->load('page_tree_update.xml');
        }
    }
}
