<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\EventListener;

use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Elasticsearch\PostConvertToDocumentEvent;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;

/**
 * This event-listener extends the view-document with the target webspace.
 */
class TargetWebspaceListener
{
    /**
     * @var RequestAnalyzer
     */
    private $requestAnalyzer;

    public function __construct(RequestAnalyzer $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * Add the proxies for content and view to view-documents.
     */
    public function onPostConvertToDocument(PostConvertToDocumentEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleViewDocumentInterface) {
            return;
        }

        $document->setTargetWebspace($this->getTargetWebspace($document));
    }

    /**
     * @return null|string
     */
    private function getTargetWebspace(ArticleViewDocumentInterface $document)
    {
        if (!$this->requestAnalyzer->getWebspace()) {
            return null;
        }

        $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();

        if ($document->getMainWebspace() === $webspaceKey
            || ($document->getAdditionalWebspaces() && in_array($webspaceKey, $document->getAdditionalWebspaces()))
        ) {
            return $webspaceKey;
        }

        return $document->getMainWebspace();
    }
}
