<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Subscriber;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Indexes article and generate route on persist and removes it from index and routing on delete.
 */
class ArticleSubscriber implements EventSubscriberInterface
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var IndexerInterface
     */
    private $liveIndexer;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var array
     */
    private $documents = [];

    /**
     * @var array
     */
    private $liveDocuments = [];

    /**
     * @param IndexerInterface $indexer
     * @param IndexerInterface $liveIndexer
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(
        IndexerInterface $indexer,
        IndexerInterface $liveIndexer,
        DocumentManagerInterface $documentManager
    ) {
        $this->indexer = $indexer;
        $this->liveIndexer = $liveIndexer;
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => [['handleScheduleIndex', -500]],
            Events::REMOVE => [
                ['handleRemove', -500],
                ['handleRemoveLive', -500],
                ['handleRemovePage', -500],
                ['handleRemovePageLive', -500],
            ],
            Events::PUBLISH => [['handleScheduleIndexLive', 0], ['handleScheduleIndex', 0]],
            Events::UNPUBLISH => 'handleUnpublish',
            Events::REMOVE_DRAFT => ['handleScheduleIndex', -1024],
            Events::FLUSH => [['handleFlush', -2048], ['handleFlushLive', -2048]],
        ];
    }

    /**
     * Schedule article document for index.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleScheduleIndex(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            if (!$document instanceof ArticlePageDocument) {
                return;
            }

            $document = $document->getParent();
        }

        $this->documents[$document->getUuid()] = [
            'uuid' => $document->getUuid(),
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Schedule article document for live index.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleScheduleIndexLive(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            if (!$document instanceof ArticlePageDocument) {
                return;
            }

            $document = $document->getParent();
        }

        $this->liveDocuments[$document->getUuid()] = [
            'uuid' => $document->getUuid(),
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Index all scheduled article documents with default indexer.
     *
     * @param FlushEvent $event
     */
    public function handleFlush(FlushEvent $event)
    {
        if (count($this->documents) < 1) {
            return;
        }

        foreach ($this->documents as $documentData) {
            $document = $this->documentManager->find($documentData['uuid'], $documentData['locale']);
            $this->documentManager->refresh($document, $documentData['locale']);

            $this->indexer->index($document);
        }
        $this->indexer->flush();
        $this->documents = [];
    }

    /**
     * Index all scheduled article documents with live indexer.
     *
     * @param FlushEvent $event
     */
    public function handleFlushLive(FlushEvent $event)
    {
        if (count($this->liveDocuments) < 1) {
            return;
        }

        foreach ($this->liveDocuments as $documentData) {
            $document = $this->documentManager->find($documentData['uuid'], $documentData['locale']);
            $this->documentManager->refresh($document, $documentData['locale']);

            $this->liveIndexer->index($document);
        }
        $this->liveIndexer->flush();
        $this->liveDocuments = [];
    }

    /**
     * Removes document from live index and unpublish document in default index.
     *
     * @param UnpublishEvent $event
     */
    public function handleUnpublish(UnpublishEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->liveIndexer->remove($document);
        $this->liveIndexer->flush();

        $this->indexer->setUnpublished($document->getUuid());
    }

    /**
     * Reindex article if a page was removed.
     *
     * @param RemoveEvent $event
     */
    public function handleRemovePage(RemoveEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $document = $document->getParent();
        $this->documents[$document->getUuid()] = [
            'uuid' => $document->getUuid(),
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Reindex article live if a page was removed.
     *
     * @param RemoveEvent $event
     */
    public function handleRemovePageLive(RemoveEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $document = $document->getParent();
        $this->liveDocuments[$document->getUuid()] = [
            'uuid' => $document->getUuid(),
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Removes article-document.
     *
     * @param RemoveEvent $event
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->indexer->remove($document);
        $this->indexer->flush();
    }

    /**
     * Removes article-document.
     *
     * @param RemoveEvent|UnpublishEvent $event
     */
    public function handleRemoveLive($event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->liveIndexer->remove($document);
        $this->liveIndexer->flush();
    }
}
