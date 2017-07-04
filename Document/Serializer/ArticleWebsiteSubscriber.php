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
use ProxyManager\Proxy\VirtualProxyInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Util\SortUtils;

/**
 * Extends serializer with additional functionality to prepare article(-page) data.
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
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @param StructureManagerInterface $structureManager
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param LazyLoadingValueHolderFactory $proxyFactory
     * @param ExtensionManagerInterface $extensionManager
     */
    public function __construct(
        StructureManagerInterface $structureManager,
        ContentTypeManagerInterface $contentTypeManager,
        LazyLoadingValueHolderFactory $proxyFactory,
        ExtensionManagerInterface $extensionManager
    ) {
        $this->structureManager = $structureManager;
        $this->contentTypeManager = $contentTypeManager;
        $this->proxyFactory = $proxyFactory;
        $this->extensionManager = $extensionManager;
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

        $extensionData = $article->getExtensionsData()->toArray();
        $extension = [];
        foreach ($extensionData as $name => $data) {
            $extension[$name] = $this->extensionManager->getExtension('article', $name)->getContentData($data);
        }

        $visitor->addData('extension', $extension);
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

        if ($article instanceof ArticlePageDocument) {
            $article = $article->getParent();
        }

        if (!$article instanceof ArticleDocument || !$context->attributes->containsKey('website')) {
            return;
        }

        $pageNumber = 1;
        if ($context->attributes->containsKey('pageNumber')) {
            $pageNumber = $context->attributes->get('pageNumber')->get();
        }

        $visitor->addData('page', $pageNumber);
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

        if (null !== $children && $context->attributes->containsKey('pageNumber')) {
            $pages = array_values(is_array($children) ? $children : iterator_to_array($children));
            $pages = SortUtils::multisort($pages, 'pageNumber');

            $pageNumber = $context->attributes->get('pageNumber')->get();
            if ($pageNumber !== 1) {
                $article = $pages[$pageNumber - 2];
            }
        }

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

        return [
            'content' => $this->createContentProxy($structure, $data),
            'view' => $this->createViewProxy($structure, $data),
        ];
    }

    /**
     * Create content-proxy for given structure.
     *
     * @param StructureInterface $structure
     * @param array $data
     *
     * @return VirtualProxyInterface
     */
    private function createContentProxy($structure, $data)
    {
        return $this->proxyFactory->createProxy(
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
    }

    /**
     * Create view-proxy for given structure.
     *
     * @param StructureInterface $structure
     * @param array $data
     *
     * @return VirtualProxyInterface
     */
    private function createViewProxy($structure, array $data)
    {
        return $this->proxyFactory->createProxy(
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
    }
}
