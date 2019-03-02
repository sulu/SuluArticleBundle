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
use Sulu\Bundle\ArticleBundle\Document\Form\ArticleDocumentType;
use Sulu\Bundle\ArticleBundle\Document\Form\ArticlePageDocumentType;
use Sulu\Bundle\ArticleBundle\Document\Structure\ArticleBridge;
use Sulu\Bundle\ArticleBundle\Document\Structure\ArticlePageBridge;
use Sulu\Bundle\ArticleBundle\Exception\ArticlePageNotFoundException;
use Sulu\Bundle\ArticleBundle\Exception\ParameterNotAllowedException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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
                            'sulu_article' => [
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
                            ArticleDocument::class => ['index' => 'article', 'decorate_index' => true],
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
                        'article' => [
                            'class' => ArticleDocument::class,
                            'phpcr_type' => 'sulu:article',
                            'form_type' => ArticleDocumentType::class,
                        ],
                        'article_page' => [
                            'class' => ArticlePageDocument::class,
                            'phpcr_type' => 'sulu:articlepage',
                            'form_type' => ArticlePageDocumentType::class,
                        ],
                    ],
                    'path_segments' => [
                        'articles' => 'articles',
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

        if ($container->hasExtension('massive_build')) {
            $container->prependExtensionConfig(
                'massive_build',
                [
                    'targets' => [
                        'prod' => [
                            'dependencies' => [
                                'article_index' => [],
                            ],
                        ],
                        'dev' => [
                            'dependencies' => [
                                'article_index' => [],
                            ],
                        ],
                        'maintain' => [
                            'dependencies' => [
                                'article_index' => [],
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/lists',
                        ],
                    ],
                    'resources' => [
                        'articles' => [
                            'routes' => [
                                'list' => 'get_articles',
                                'detail' => 'get_article',
                            ],
                        ],
                        'articles_seo' => [
                            'routes' => [
                                'list' => 'get_article-seos',
                                'detail' => 'get_article-seo',
                            ],
                        ],
                        'articles_excerpt' => [
                            'routes' => [
                                'list' => 'get_article-excerpts',
                                'detail' => 'get_article-excerpt',
                            ],
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
        $container->setParameter('sulu_article.default_main_webspace', $config['default_main_webspace']);
        $container->setParameter('sulu_article.default_additional_webspaces', $config['default_additional_webspaces']);
        $container->setParameter('sulu_article.types', $config['types']);
        $container->setParameter('sulu_article.documents', $config['documents']);
        $container->setParameter('sulu_article.view_document.article.class', $config['documents']['article']['view']);
        $container->setParameter('sulu_article.display_tab_all', $config['display_tab_all']);
        $container->setParameter('sulu_article.smart_content.default_limit', $config['smart_content']['default_limit']);
        $container->setParameter('sulu_article.search_fields', $config['search_fields']);

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
        } elseif ('task' === $config['content_types']['page_tree_route']['page_route_cascade']) {
            throw new InvalidConfigurationException(
                'You need to install the SuluAutomationBundle to use task cascading!'
            );
        }

        $container->setAlias(
            'sulu_article.page_tree_route.updater',
            'sulu_article.page_tree_route.updater.' . $config['content_types']['page_tree_route']['page_route_cascade']
        );

        $loader->load('page_tree_move.xml');
        if ($config['content_types']['page_tree_route']['page_route_cascade'] !== 'off') {
            $loader->load('page_tree_update.xml');
        }

        $this->appendDefaultAuthor($config, $container);
        $this->appendArticlePageConfig($container);

        $articleDocument = ArticleDocument::class;
        $articlePageDocument = ArticlePageDocument::class;

        foreach ($container->getParameter('sulu_document_manager.mapping') as $mapping) {
            if ('article' == $mapping['alias']) {
                $articleDocument = $mapping['class'];
            }

            if ('article_page' == $mapping['alias']) {
                $articlePageDocument = $mapping['class'];
            }
        }

        $container->setParameter('sulu_article.article_document.class', $articleDocument);
        $container->setParameter('sulu_article.article_page_document.class', $articlePageDocument);
    }

    /**
     * Append configuration for article "set_default_author".
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function appendDefaultAuthor(array $config, ContainerBuilder $container)
    {
        $mapping = $container->getParameter('sulu_document_manager.mapping');
        foreach ($mapping as $key => $item) {
            if ('article' === $item['alias']) {
                $mapping[$key]['set_default_author'] = $config['default_author'];
            }
        }

        $container->setParameter('sulu_document_manager.mapping', $mapping);
        $container->setParameter('sulu_article.default_author', $config['default_author']);
    }

    /**
     * Append configuration for article-page (cloned from article).
     *
     * @param ContainerBuilder $container
     */
    private function appendArticlePageConfig(ContainerBuilder $container)
    {
        $paths = $container->getParameter('sulu.content.structure.paths');
        $paths['article_page'] = $this->cloneArticleConfig($paths['article'], 'article_page');
        $container->setParameter('sulu.content.structure.paths', $paths);

        $defaultTypes = $container->getParameter('sulu.content.structure.default_types');
        $defaultTypes['article_page'] = $defaultTypes['article'];
        $container->setParameter('sulu.content.structure.default_types', $defaultTypes);
    }

    /**
     * Clone given path configuration and use given type.
     *
     * @param array $config
     * @param string $type
     *
     * @return array
     */
    private function cloneArticleConfig(array $config, $type)
    {
        $result = [];
        foreach ($config as $item) {
            $result[] = ['path' => $item['path'], 'type' => $type];
        }

        return $result;
    }
}
