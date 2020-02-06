<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Util\SortUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Indexes article and generate route on persist and removes it from index and routing on delete.
 */
class ArticleSubscriber implements EventSubscriberInterface
{
    const PAGES_PROPERTY = 'suluPages';

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
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var array
     */
    private $documents = [];

    /**
     * @var array
     */
    private $liveDocuments = [];

    /**
     * @var array
     */
    private $children = [];

    /**
     * @param IndexerInterface $indexer
     * @param IndexerInterface $liveIndexer
     * @param DocumentManagerInterface $documentManager
     * @param DocumentInspector $documentInspector
     * @param PropertyEncoder $propertyEncoder
     */
    public function __construct(
        IndexerInterface $indexer,
        IndexerInterface $liveIndexer,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        PropertyEncoder $propertyEncoder
    ) {
        $this->indexer = $indexer;
        $this->liveIndexer = $liveIndexer;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->propertyEncoder = $propertyEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => [
                ['hydratePageData', -2000],
            ],
            Events::PERSIST => [
                ['handleScheduleIndex', -500],
                ['handleChildrenPersist', 0],
                ['persistPageData', -2000],
            ],
            Events::REMOVE => [
                ['handleRemove', -500],
                ['handleRemoveLive', -500],
                ['handleRemovePage', -500],
            ],
            Events::PUBLISH => [
                ['handleScheduleIndexLive', 0],
                ['handleScheduleIndex', 0],
                ['synchronizeChildren', 0],
                ['publishChildren', 0],
                ['persistPageData', -2000],
            ],
            Events::REORDER => [['persistPageDataOnReorder', -2000]],
            Events::UNPUBLISH => 'handleUnpublish',
            Events::REMOVE_DRAFT => [['handleScheduleIndex', -1024], ['removeDraftChildren', 0]],
            Events::FLUSH => [['handleFlush', -2048], ['handleFlushLive', -2048]],
            Events::COPY => ['handleCopy'],
            Events::METADATA_LOAD => ['handleMetadataLoad'],
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
     * Syncs children between live and draft.
     *
     * @param PublishEvent $event
     */
    public function synchronizeChildren(PublishEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $liveNode = $event->getNode();
        $draftNode = $this->documentInspector->getNode($document);

        $liveChildren = $this->getChildren($liveNode);
        $draftChildren = $this->getChildren($draftNode);
        $removedChildrenIds = array_diff(array_keys($liveChildren), array_keys($draftChildren));

        foreach ($removedChildrenIds as $removedChildrenId) {
            $liveChildren[$removedChildrenId]->remove();
        }
    }

    /**
     * Returns children of given node.
     *
     * @param NodeInterface $node
     *
     * @return NodeInterface[]
     */
    private function getChildren(NodeInterface $node)
    {
        $result = [];
        foreach ($node->getNodes() as $child) {
            $result[$child->getIdentifier()] = $child;
        }

        return $result;
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

        $children = iterator_to_array($document->getChildren());
        foreach ($children as $child) {
            if (LocalizationState::GHOST !== $this->documentInspector->getLocalizationState($child)) {
                $this->documentManager->publish($child, $event->getLocale());
            }
        }
    }

    /**
     * Persist page-data for reordering children.
     *
     * @param ReorderEvent $event
     */
    public function persistPageDataOnReorder(ReorderEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $document = $document->getParent();
        $node = $this->documentInspector->getNode($document);

        $this->setPageData($document, $node, $document->getLocale());

        $document->setWorkflowStage(WorkflowStage::TEST);
        $this->documentManager->persist($document, $this->documentInspector->getLocale($document));

        $this->documents[$document->getUuid()] = [
            'uuid' => $document->getUuid(),
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Persist page-data.
     *
     * @param PersistEvent|PublishEvent $event
     */
    public function persistPageData($event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->setPageData($document, $event->getNode(), $event->getLocale());
    }

    /**
     * Set page-data for given document on given node.
     *
     * @param ArticleDocument $document
     * @param NodeInterface $node
     * @param string $locale
     */
    private function setPageData(ArticleDocument $document, NodeInterface $node, $locale)
    {
        $pages = [
            [
                'uuid' => $document->getUuid(),
                'title' => $document->getPageTitle() ?: $document->getTitle(),
                'routePath' => $document->getRoutePath(),
                'pageNumber' => $document->getPageNumber(),
            ],
        ];

        foreach ($document->getChildren() as $child) {
            if ($child instanceof ArticlePageDocument
                && LocalizationState::GHOST !== $this->documentInspector->getLocalizationState($child)
            ) {
                $pages[] = [
                    'uuid' => $child->getUuid(),
                    'title' => $child->getPageTitle(),
                    'routePath' => $child->getRoutePath(),
                    'pageNumber' => $child->getPageNumber(),
                ];
            }
        }

        $pages = SortUtils::multisort($pages, '[pageNumber]');

        $document->setPages($pages);
        $node->setProperty(
            $this->propertyEncoder->localizedSystemName(self::PAGES_PROPERTY, $locale),
            json_encode($pages)
        );
    }

    /**
     * Hydrate page-data.
     *
     * @param HydrateEvent $event
     */
    public function hydratePageData(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $pages = $event->getNode()->getPropertyValueWithDefault(
            $this->propertyEncoder->localizedSystemName(self::PAGES_PROPERTY, $document->getOriginalLocale()),
            json_encode([])
        );
        $pages = json_decode($pages, true);

        if (LocalizationState::SHADOW === $this->documentInspector->getLocalizationState($document)) {
            $pages = $this->loadPageDataForShadow($event->getNode(), $document, $pages);
        }

        $document->setPages($pages);
    }

    /**
     * Load `routePath` from current locale into `pageData`.
     *
     * @param NodeInterface $node
     * @param ArticleDocument $document
     * @param array $originalPages
     *
     * @return array
     */
    private function loadPageDataForShadow(NodeInterface $node, ArticleDocument $document, array $originalPages)
    {
        $pages = $node->getPropertyValueWithDefault(
            $this->propertyEncoder->localizedSystemName(self::PAGES_PROPERTY, $document->getLocale()),
            json_encode([])
        );
        $pages = json_decode($pages, true);

        for ($i = 0; $i < count($originalPages); ++$i) {
            $pages[$i]['routePath'] = $originalPages[$i]['routePath'];
        }

        return $pages;
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
            if (LocalizationState::GHOST === $this->documentInspector->getLocalizationState($child)) {
                continue;
            }

            try {
                $this->documentManager->removeDraft($child, $event->getLocale());
            } catch (PathNotFoundException $exception) {
                // child is not available in live workspace
                $node = $this->documentInspector->getNode($child);
                $node->remove();
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
            $this->documentManager->refresh($document);

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
            $this->documentManager->refresh($document);

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

        $this->indexer->setUnpublished($document->getUuid(), $event->getLocale());
        $this->indexer->flush();
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
     * Schedule all children.
     *
     * @param PersistEvent $event
     */
    public function handleChildrenPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        foreach ($document->getChildren() as $childDocument) {
            if (!$childDocument instanceof ArticlePageDocument) {
                continue;
            }

            $localizationState = $this->documentInspector->getLocalizationState($childDocument);

            if (LocalizationState::GHOST === $localizationState) {
                continue;
            }

            $changed = false;

            if ($document->getStructureType() !== $childDocument->getStructureType()) {
                $childDocument->setStructureType($document->getStructureType());
                $changed = true;
            }

            if ($document->getShadowLocale() !== $childDocument->getShadowLocale()) {
                $childDocument->setShadowLocale($document->getShadowLocale());
                $changed = true;
            }

            if ($document->isShadowLocaleEnabled() !== $childDocument->isShadowLocaleEnabled()) {
                $childDocument->setShadowLocaleEnabled($document->isShadowLocaleEnabled());
                $changed = true;
            }

            if ($changed) {
                $this->documentManager->persist(
                    $childDocument,
                    $childDocument->getLocale(),
                    [
                        'clear_missing_content' => false,
                        'auto_name' => false,
                        'auto_rename' => false,
                    ]
                );
            }
        }
    }

    /**
     * Extend metadata for article-page.
     *
     * @param MetadataLoadEvent $event
     */
    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        if (ArticleDocument::class !== $event->getMetadata()->getClass()) {
            return;
        }

        $event->getMetadata()->addFieldMapping(
            'pageTitle',
            [
                'encoding' => 'system_localized',
                'property' => 'suluPageTitle',
            ]
        );
    }
}
