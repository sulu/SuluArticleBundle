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
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
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
     * @var DocumentInspector
     */
    private $documentInspector;

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
     * @param DocumentInspector $documentInspector
     */
    public function __construct(
        IndexerInterface $indexer,
        IndexerInterface $liveIndexer,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector
    ) {
        $this->indexer = $indexer;
        $this->liveIndexer = $liveIndexer;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => [['handleScheduleIndex', -500], ['setChildrenStructureType', 0]],
            Events::REMOVE => [
                ['handleRemove', -500],
                ['handleRemoveLive', -500],
                ['handleRemovePage', -500],
                ['handleRemovePageLive', -500],
            ],
            Events::PUBLISH => [
                ['handleScheduleIndexLive', 0],
                ['handleScheduleIndex', 0],
                ['publishChildren', 0],
            ],
            Events::UNPUBLISH => 'handleUnpublish',
            Events::REMOVE_DRAFT => [['handleScheduleIndex', -1024], ['removeDraftChildren', 0]],
            Events::FLUSH => [['handleFlush', -2048], ['handleFlushLive', -2048]],
            Events::COPY => ['handleCopy'],
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
     * Publish pages when article will be published.
     *
     * @param PublishEvent $event
     */
    public function publishChildren(PublishEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        foreach ($document->getChildren() as $child) {
            if ($this->documentInspector->getLocalizationState($child) !== LocalizationState::GHOST) {
                $this->documentManager->publish($child, $event->getLocale());
            }
        }
    }

    /**
     * Remove draft from children.
     *
     * @param RemoveDraftEvent $event
     */
    public function removeDraftChildren(RemoveDraftEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        foreach ($document->getChildren() as $child) {
            if ($this->documentInspector->getLocalizationState($child) !== LocalizationState::GHOST) {
                $this->documentManager->removeDraft($child, $event->getLocale());
            }
        }
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

    /**
     * Schedule document to index.
     *
     * @param CopyEvent $event
     */
    public function handleCopy(CopyEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $uuid = $event->getCopiedNode()->getIdentifier();
        $this->documents[$uuid] = [
            'uuid' => $uuid,
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Set structure-type to pages.
     *
     * @param PersistEvent $event
     */
    public function setChildrenStructureType(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        foreach ($document->getChildren() as $child) {
            if ($this->documentInspector->getLocalizationState($child) !== LocalizationState::GHOST
                && $document->getStructureType() !== $child->getStructureType()
            ) {
                $child->setStructureType($document->getStructureType());
                $this->documentManager->persist($child, $event->getLocale(), $event->getOptions());
            }
        }
    }
}
