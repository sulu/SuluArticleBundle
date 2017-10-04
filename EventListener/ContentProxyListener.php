<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\EventListener;

use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Structure\ContentProxyFactory;
use Sulu\Bundle\ArticleBundle\Elasticsearch\PostConvertToDocumentEvent;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;

/**
 * This event-listener extends the view-document with the content-proxy.
 */
class ContentProxyListener
{
    /**
     * @var ContentProxyFactory
     */
    private $contentProxyFactory;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    public function __construct(ContentProxyFactory $contentProxyFactory, StructureManagerInterface $structureManager)
    {
        $this->contentProxyFactory = $contentProxyFactory;
        $this->structureManager = $structureManager;
    }

    /**
     * Add the proxies for content and view to view-documents.
     *
     * @param PostConvertToDocumentEvent $event
     */
    public function onPostConvertToDocument(PostConvertToDocumentEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleViewDocumentInterface) {
            return;
        }

        $structure = $this->structureManager->getStructure($document->getStructureType(), 'article');
        $structure->setLanguageCode($document->getLocale());

        list($content, $view) = $this->getProxies($document->getContentData(), $structure);

        $document->setContent($content);
        $document->setView($view);

        foreach ($document->getPages() as $page) {
            list($page->content, $page->view) = $this->getProxies($page->contentData, $structure);
        }
    }

    /**
     * Create content and view proxy for given content-data.
     *
     * @param string $contentData
     * @param StructureInterface $structure
     *
     * @return array
     */
    private function getProxies($contentData, StructureInterface $structure)
    {
        $contentData = $contentData ?: '{}';
        $data = json_decode($contentData, true);

        $content = $this->contentProxyFactory->createContentProxy($structure, $data);
        $view = $this->contentProxyFactory->createViewProxy($structure, $data);

        return [$content, $view];
    }
}
