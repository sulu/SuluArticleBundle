<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Structure\ContentProxyFactory;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;

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
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var ContentProxyFactory
     */
    private $contentProxyFactory;

    public function __construct(
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ContentProxyFactory $contentProxyFactory
    ) {
        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->contentProxyFactory = $contentProxyFactory;
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
     */
    public function resolveContentForArticleOnPostSerialize(ObjectEvent $event)
    {
        $article = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!$article instanceof ArticleDocument || !$context->attributes->containsKey('website')) {
            return;
        }

        if ($context->attributes->containsKey('pageNumber')) {
            $article = $this->getArticleForPage($article, $context->attributes->get('pageNumber')->get());
        }

        $content = $this->resolve($article);
        foreach ($content as $name => $value) {
            $visitor->addData($name, $value);
        }
    }

    /**
     * Returns article page by page-number.
     *
     * @param int $pageNumber
     *
     * @return ArticleDocument
     */
    private function getArticleForPage(ArticleDocument $article, $pageNumber)
    {
        $children = $article->getChildren();
        if (null === $children || 1 === $pageNumber) {
            return $article;
        }

        foreach ($children as $child) {
            if ($child instanceof ArticlePageDocument && $child->getPageNumber() === $pageNumber) {
                return $child;
            }
        }

        return $article;
    }

    /**
     * Resolve content on serialization.
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
     * @return array
     */
    private function resolve(ArticleInterface $article)
    {
        $structure = $this->structureManager->getStructure($article->getStructureType(), 'article');
        $structure->setDocument($article);

        $data = $article->getStructure()->toArray();

        return [
            'content' => $this->contentProxyFactory->createContentProxy($structure, $data),
            'view' => $this->contentProxyFactory->createViewProxy($structure, $data),
        ];
    }
}
