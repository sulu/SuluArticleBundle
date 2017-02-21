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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
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
     * @var RouteManagerInterface
     */
    private $routeManager;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
     * @param RouteManagerInterface $routeManager
     * @param RouteRepositoryInterface $routeRepository
     * @param EntityManagerInterface $entityManager
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(
        IndexerInterface $indexer,
        IndexerInterface $liveIndexer,
        RouteManagerInterface $routeManager,
        RouteRepositoryInterface $routeRepository,
        EntityManagerInterface $entityManager,
        DocumentManagerInterface $documentManager
    ) {
        $this->indexer = $indexer;
        $this->liveIndexer = $liveIndexer;
        $this->routeManager = $routeManager;
        $this->routeRepository = $routeRepository;
        $this->entityManager = $entityManager;
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => [['handleHydrate', -500]],
            Events::PERSIST => [['handleRoute', 0], ['handleRouteUpdate', 0], ['handleScheduleIndex', -500]],
            Events::REMOVE => [['handleRemove', -500], ['handleRemoveLive', -500]],
            Events::METADATA_LOAD => 'handleMetadataLoad',
            Events::PUBLISH => [['handleScheduleIndexLive', 0], ['handleScheduleIndex', 0]],
            Events::UNPUBLISH => 'handleUnpublish',
            Events::CONFIGURE_OPTIONS => 'configureOptions',
            Events::REMOVE_DRAFT => ['handleScheduleIndex', -1024],
            Events::FLUSH => [['handleFlush', -2048], ['handleFlushLive', -2048]],
        ];
    }

    /**
     * Load route for article-document.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument || null === $document->getRoutePath()) {
            return;
        }

        $route = $this->routeRepository->findByPath($document->getRoutePath(), $document->getOriginalLocale());
        if (!$route) {
            return;
        }

        $document->setRoute($route);
    }

    /**
     * Generate route for article-document.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleRoute(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument || null !== $document->getRoutePath()) {
            return;
        }

        $document->setUuid($event->getNode()->getIdentifier());

        $route = $this->routeManager->create($document);
        $this->entityManager->persist($route);
        $this->entityManager->flush();
    }

    /**
     * Update route for article-document if route-path was changed.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleRouteUpdate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument
            || null === $document->getRoute()
            || null === ($routePath = $event->getOption('route_path'))
        ) {
            return;
        }

        $route = $this->routeManager->update($document, $routePath);
        $document->setRoutePath($route->getPath());
        $this->entityManager->persist($route);
        $this->entityManager->flush();
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
            return;
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
            return;
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

        foreach ($this->documents as $document) {
            $this->indexer->index(
                    $this->documentManager->find($document['uuid'],
                    $document['locale']
                )
            );
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

        foreach ($this->liveDocuments as $document) {
            $this->liveIndexer->index(
                $this->documentManager->find(
                    $document['uuid'],
                    $document['locale']
                )
            );
        }
        $this->liveIndexer->flush();
        $this->liveDocuments = [];
    }

    /**
     * Indexes for article-document in live index.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleIndexLive(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->liveIndexer->index($document);
        $this->liveIndexer->flush();
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
     * Add route to metadata.
     *
     * @param MetadataLoadEvent $event
     */
    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        if ($event->getMetadata()->getClass() !== ArticleDocument::class) {
            return;
        }

        $metadata = $event->getMetadata();
        $metadata->addFieldMapping(
            'routePath',
            [
                'encoding' => 'system_localized',
                'property' => 'routePath',
            ]
        );
        $metadata->addFieldMapping(
            'authored',
            [
                'encoding' => 'system_localized',
                'property' => 'authored',
            ]
        );
        $metadata->addFieldMapping(
            'author',
            [
                'encoding' => 'system_localized',
                'property' => 'author',
            ]#
        );
    }

    /**
     * Add route-path to options.
     *
     * @param ConfigureOptionsEvent $event
     */
    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();
        $options->setDefaults(['route_path' => null]);
    }
}
