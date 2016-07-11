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
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
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
     * @param IndexerInterface $indexer
     * @param RouteManagerInterface $routeManager
     * @param RouteRepositoryInterface $routeRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        IndexerInterface $indexer,
        RouteManagerInterface $routeManager,
        RouteRepositoryInterface $routeRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->indexer = $indexer;
        $this->routeManager = $routeManager;
        $this->routeRepository = $routeRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => [['handleHydrate', -500]],
            Events::PERSIST => [['handleRoute', 0], ['handleIndex', -500]],
            Events::REMOVE => [['handleRemove', -500]],
            Events::METADATA_LOAD => 'handleMetadataLoad',
        ];
    }

    /**
     * Load route for article-document.
     *
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument || null === $document->getRoutePath()) {
            return;
        }

        $document->setRoute($this->routeRepository->findByPath($document->getRoutePath(), $event->getLocale()));
    }

    /**
     * Generate route for article-document.
     *
     * @param PersistEvent $event
     */
    public function handleRoute(PersistEvent $event)
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
     * Indexes for article-document.
     *
     * @param PersistEvent $event
     */
    public function handleIndex(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->indexer->index($document);
        $this->indexer->flush();
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
    }
}
