<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
                            'paths' => [
                                'article' => [
                                    'path' => '%kernel.project_dir%/config/templates/articles',
                                    'type' => 'article',
                                ],
                                'article_page' => [
                                    'path' => '%kernel.project_dir%/config/templates/articles',
                                    'type' => 'article_page',
                                ],
                            ],
                            'type_map' => [
                                'article' => ArticleBridge::class,
                                'article_page' => ArticlePageBridge::class,
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_route')) {
            $container->prependExtensionConfig(
                'sulu_route',
                [
                    'mappings' => [
                        'Sulu\Bundle\ArticleBundle\Document\ArticleDocument' => [
                            'resource_key' => 'articles',
                        ],
                        'Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument' => [
                            'resource_key' => 'article_pages',
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
                'sulu_page',
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
                    'forms' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/forms',
                        ],
                    ],
                    'resources' => [
                        'articles' => [
                            'routes' => [
                                'list' => 'sulu_article.get_articles',
                                'detail' => 'sulu_article.get_article',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'article_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'articles',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'articles',
                                        'display_properties' => ['title', 'routePath'],
                                        'icon' => 'su-newspaper',
                                        'label' => 'sulu_article.selection_label',
                                        'overlay_title' => 'sulu_article.selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                        'single_selection' => [
                            'single_article_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'articles',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'articles',
                                        'display_properties' => ['title'],
                                        'empty_text' => 'sulu_article.no_article_selected',
                                        'icon' => 'su-newspaper',
                                        'overlay_title' => 'sulu_article.single_selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('ongr_elasticsearch')) {
            $configs = $container->getExtensionConfig($this->getAlias());
            $config = $this->processConfiguration(new Configuration(), $configs);

            $indexName = $config['index_name'];
            $hosts = $config['hosts'];

            $ongrElasticSearchConfig = [
                'managers' => [
                    'default' => [
                        'index' => [
                            'index_name' => $indexName,
                        ],
                        'mappings' => ['SuluArticleBundle'],
                    ],
                    'live' => [
                        'index' => [
                            'index_name' => $indexName . '_live',
                        ],
                        'mappings' => ['SuluArticleBundle'],
                    ],
                ],
            ];

            if (count($hosts) > 0) {
                $ongrElasticSearchConfig['managers']['default']['index']['hosts'] = $hosts;
                $ongrElasticSearchConfig['managers']['live']['index']['hosts'] = $hosts;
            }

            $container->prependExtensionConfig(
                'ongr_elasticsearch',
                $ongrElasticSearchConfig
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

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $bundles = $container->getParameter('kernel.bundles');
        if (array_key_exists('SuluAutomationBundle', $bundles)) {
            $loader->load('automation.xml');
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
     */
    private function appendDefaultAuthor(array $config, ContainerBuilder $container): void
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
     */
    private function appendArticlePageConfig(ContainerBuilder $container): void
    {
        $paths = $container->getParameter('sulu.content.structure.paths');
        $paths['article_page'] = $this->cloneArticleConfig($paths['article'], 'article_page');
        $container->setParameter('sulu.content.structure.paths', $paths);

        $defaultTypes = $container->getParameter('sulu.content.structure.default_types');
        if (isset($defaultTypes['article'])) {
            $defaultTypes['article_page'] = $defaultTypes['article'];
            $container->setParameter('sulu.content.structure.default_types', $defaultTypes);
        }
    }

    /**
     * Clone given path configuration and use given type.
     */
    private function cloneArticleConfig(array $config, string $type): array
    {
        $result = [];
        foreach ($config as $item) {
            $result[] = ['path' => $item['path'], 'type' => $type];
        }

        return $result;
    }
}
