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
use JMS\Serializer\Metadata\StaticPropertyMetadata;
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
                'format' => 'json',
                'method' => 'resolveContentOnPostSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'resolveContentForArticleOnPostSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'resolveContentForArticlePageOnPostSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
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

        if (!$article instanceof ArticleInterface || !$context->hasAttribute('website')) {
            return;
        }

        $uuid = $article->getArticleUuid();
        $visitor->visitProperty(new StaticPropertyMetadata('', 'uuid', $uuid), $uuid);

        $pageUuid = $article->getPageUuid();
        $visitor->visitProperty(new StaticPropertyMetadata('', 'pageUuid', $pageUuid), $pageUuid);

        // TODO extension data should not be serialized because medias should be available as entities
        /*
        $extensionData = $article->getExtensionsData()->toArray();
        $extension = [];
        foreach ($extensionData as $name => $data) {
            $extension[$name] = $this->extensionManager->getExtension('article', $name)->getContentData($data);
        }

        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'extension', $extension),
            $extension
        );

        $visitor->setData('extension', $extension);
        */
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

        if (!$article instanceof ArticleDocument || !$context->hasAttribute('website')) {
            return;
        }

        $pageNumber = 1;
        if ($context->hasAttribute('pageNumber')) {
            $pageNumber = $context->getAttribute('pageNumber');
        }

        $visitor->visitProperty(new StaticPropertyMetadata('', 'page', $pageNumber), $pageNumber);

        $pages = $article->getPages();
        $visitor->visitProperty(new StaticPropertyMetadata('', 'pages', $pages), $pages);
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

        if (!$article instanceof ArticleDocument || !$context->hasAttribute('website')) {
            return;
        }

        if ($context->hasAttribute('pageNumber')) {
            $article = $this->getArticleForPage($article, $context->getAttribute('pageNumber'));
        }

        $content = $this->resolve($article);
        // TODO extension data should not be serialized
        /*
        foreach ($content as $name => $value) {
            $visitor->setData($name, $value);
        }
        */
    }

    /**
     * Returns article page by page-number.
     *
     * @param ArticleDocument $article
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
     *
     * @param ObjectEvent $event
     */
    public function resolveContentForArticlePageOnPostSerialize(ObjectEvent $event)
    {
        $article = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!$article instanceof ArticlePageDocument || !$context->hasAttribute('website')) {
            return;
        }

        $content = $this->resolve($article);

        foreach ($content as $name => $value) {
            $visitor->setData($name, $value);
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
            'content' => $this->contentProxyFactory->createContentProxy($structure, $data),
            'view' => $this->contentProxyFactory->createViewProxy($structure, $data),
        ];
    }
}
