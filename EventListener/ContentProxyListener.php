<?php

namespace Sulu\Bundle\ArticleBundle\EventListener;

use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Structure\ContentProxyFactory;
use Sulu\Bundle\ArticleBundle\Elasticsearch\PostConvertToDocumentEvent;
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

    public function onPostConvertToDocument(PostConvertToDocumentEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleViewDocumentInterface) {
            return;
        }

        $structure = $this->structureManager->getStructure($document->getStructureType(), 'article');
        $structure->setLanguageCode($document->getLocale());

        $contentData = $document->getContentData() ?: '{}';
        $data = json_decode($contentData, true);

        $document->setContent($this->contentProxyFactory->createContentProxy($structure, $data));
        $document->setView($this->contentProxyFactory->createViewProxy($structure, $data));
    }
}
