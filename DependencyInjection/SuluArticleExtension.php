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
use Sulu\Component\Content\Compat\Structure\StructureBridge;
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
                                'article' => StructureBridge::class,
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
                    ],
                    'path_segments' => [
                        'articles' => 'articles',
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

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }
}
