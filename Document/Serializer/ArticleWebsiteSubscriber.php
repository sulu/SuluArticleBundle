<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;

/**
 * Extends serializer with addtional functionallity to prepare article(-page) data.
 */
class ArticleWebsiteSubscriber implements EventSubscriberInterface
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @param StructureManagerInterface $structureManager
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param LazyLoadingValueHolderFactory $proxyFactory
     */
    public function __construct(
        StructureManagerInterface $structureManager,
        ContentTypeManagerInterface $contentTypeManager,
        LazyLoadingValueHolderFactory $proxyFactory
    ) {
        $this->structureManager = $structureManager;
        $this->contentTypeManager = $contentTypeManager;
        $this->proxyFactory = $proxyFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'array',
                'method' => 'resolveContentOnPostSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'array',
                'method' => 'resolveContentForArticleOnPostSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'array',
                'method' => 'resolveContentForArticlePageOnPostSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'array',
                'method' => 'appendPageData',
            ],
        ];
    }

    /**
     * Resolve content on serialization.
     *
     * @param ObjectEvent $event
     */
    public function resolveContentOnPostSerialize(ObjectEvent $event)
    {
        $article = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!$article instanceof ArticleInterface || !$context->attributes->containsKey('website')) {
            return;
        }

        $visitor->addData('uuid', $context->accept($article->getArticleUuid()));
        $visitor->addData('pageUuid', $context->accept($article->getPageUuid()));
        $visitor->addData('extension', $context->accept($article->getExtensionsData()->toArray()));
    }

    /**
     * Append page data.
     *
     * @param ObjectEvent $event
     */
    public function appendPageData(ObjectEvent $event)
    {
        $article = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!$article instanceof ArticleDocument || !$context->attributes->containsKey('website')) {
            return;
        }

        $visitor->addData('pages', $context->accept($article->getPages()));
    }

    /**
     * Resolve content on serialization.
     *
     * @param ObjectEvent $event
     */
    public function resolveContentForArticleOnPostSerialize(ObjectEvent $event)
    {
        $article = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!$article instanceof ArticleDocument || !$context->attributes->containsKey('website')) {
            return;
        }

        $children = $article->getChildren();

        $pageNumber = 1;
        if (null !== $children && $context->attributes->containsKey('pageNumber')) {
            $pages = array_values(iterator_to_array($children));
            $pageNumber = $context->attributes->get('pageNumber')->get();
            if ($pageNumber !== 1) {
                $article = $pages[$pageNumber - 2];
            }
        }

        $visitor->addData('page', $pageNumber);
        $content = $this->resolve($article);
        foreach ($content as $name => $value) {
            $visitor->addData($name, $value);
        }
    }

    /**
     * Resolve content on serialization.
     *
     * @param ObjectEvent $event
     */
    public function resolveContentForArticlePageOnPostSerialize(ObjectEvent $event)
    {
        $article = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!$article instanceof ArticlePageDocument || !$context->attributes->containsKey('website')) {
            return;
        }

        $content = $this->resolve($article);
        foreach ($content as $name => $value) {
            $visitor->addData($name, $value);
        }
    }

    /**
     * Returns content and view of article.
     *
     * @param ArticleInterface $article
     *
     * @return array
     */
    private function resolve(ArticleInterface $article)
    {
        $structure = $this->structureManager->getStructure($article->getStructureType(), 'article');
        $structure->setDocument($article);

        $data = $article->getStructure()->toArray();

        $content = $this->proxyFactory->createProxy(
            \ArrayObject::class,
            function (
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($structure, $data) {
                $content = [];
                foreach ($structure->getProperties(true) as $child) {
                    if (array_key_exists($child->getName(), $data)) {
                        $child->setValue($data[$child->getName()]);
                    }

                    $contentType = $this->contentTypeManager->get($child->getContentTypeName());
                    $content[$child->getName()] = $contentType->getContentData($child);
                }

                $initializer = null;
                $wrappedObject = new \ArrayObject($content);

                return true;
            }
        );
        $view = $this->proxyFactory->createProxy(
            \ArrayObject::class,
            function (
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($structure, $data) {
                $view = [];
                foreach ($structure->getProperties(true) as $child) {
                    if (array_key_exists($child->getName(), $data)) {
                        $child->setValue($data[$child->getName()]);
                    }

                    $contentType = $this->contentTypeManager->get($child->getContentTypeName());
                    $view[$child->getName()] = $contentType->getViewData($child);
                }

                $initializer = null;
                $wrappedObject = new \ArrayObject($view);

                return true;
            }
        );

        return ['content' => $content, 'view' => $view];
    }
}
