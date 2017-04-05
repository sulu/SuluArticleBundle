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
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutableBehavior;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events to create/update/remove routes.
 */
class RoutableSubscriber implements EventSubscriberInterface
{
    const ROUTE_FIELD = 'routePath';

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
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @param RouteManagerInterface $routeManager
     * @param RouteRepositoryInterface $routeRepository
     * @param EntityManagerInterface $entityManager
     * @param DocumentManagerInterface $documentManager
     * @param DocumentInspector $documentInspector
     * @param PropertyEncoder $propertyEncoder
     */
    public function __construct(
        RouteManagerInterface $routeManager,
        RouteRepositoryInterface $routeRepository,
        EntityManagerInterface $entityManager,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        PropertyEncoder $propertyEncoder
    ) {
        $this->routeManager = $routeManager;
        $this->routeRepository = $routeRepository;
        $this->entityManager = $entityManager;
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
            Events::HYDRATE => [['handleHydrate', -500]],
            Events::PERSIST => [['handleRouteUpdate', 1], ['handleRoute', 0]],
            Events::REMOVE => [
                // high priority to ensure nodes are not deleted until we iterate over children
                ['handleRemove', 1024],
            ],
            Events::COPY => ['handleCopy', -2000],
            Events::METADATA_LOAD => 'handleMetadataLoad',
            Events::CONFIGURE_OPTIONS => 'configureOptions',
        ];
    }

    /**
     * Load route.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior || null === $document->getRoutePath()) {
            return;
        }

        $route = $this->routeRepository->findByPath($document->getRoutePath(), $document->getOriginalLocale());
        if (!$route) {
            return;
        }

        $document->setRoute($route);
    }

    /**
     * Generate route.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleRoute(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior || null !== $document->getRoutePath()) {
            return;
        }

        $document->setUuid($event->getNode()->getIdentifier());

        $route = $this->routeManager->create($document, $event->getOption('route_path'));
        $this->entityManager->persist($route);
        $this->entityManager->flush();
    }

    /**
     * Update route if route-path was changed.
     *
     * @param AbstractMappingEvent $event
     */
    public function handleRouteUpdate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior
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
     * Removes route.
     *
     * @param RemoveEvent $event
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        $route = $this->routeRepository->findByPath($document->getRoutePath(), $document->getOriginalLocale());
        if (!$route) {
            return;
        }

        $this->entityManager->remove($route);

        if ($document instanceof ChildrenBehavior) {
            $this->removeChildRoutes($document);
        }

        $this->entityManager->flush();
    }

    /**
     * Update routes for copied article.
     *
     * @param CopyEvent $event
     */
    public function handleCopy(CopyEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        $locales = $this->documentInspector->getLocales($document);
        foreach ($locales as $locale) {
            /** @var ArticleDocument $localizedDocument */
            $localizedDocument = $this->documentManager->find($event->getCopiedPath(), $locale);

            $localizedDocument->removeRoute();
            $route = $this->routeManager->create($localizedDocument);
            $document->setRoutePath($route->getPath());
            $this->entityManager->persist($route);

            $event->getCopiedNode()->setProperty(
                $this->propertyEncoder->localizedSystemName(self::ROUTE_FIELD, $locale),
                $route->getPath()
            );
        }

        $this->entityManager->flush();
    }
        /**
         * Iterate over children and remove routes.
     *
     * @param ChildrenBehavior $document
     */
    private function removeChildRoutes(ChildrenBehavior $document)
    {
        foreach ($document->getChildren() as $child) {
            if ($child instanceof RoutableBehavior) {
                $this->removeChildRoute($child);
            }

            if ($child instanceof ChildrenBehavior) {
                $this->removeChildRoutes($child);
            }
        }
    }

    /**
     * Removes route if exists.
     *
     * @param RoutableBehavior $document
     */
    private function removeChildRoute(RoutableBehavior $document)
    {
        $route = $this->routeRepository->findByPath($document->getRoutePath(), $document->getOriginalLocale());
        if ($route) {
            $this->entityManager->remove($route);
        }
    }

    /**
     * Add route to metadata.
     *
     * @param MetadataLoadEvent $event
     */
    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        if (!$event->getMetadata()->getReflectionClass()->implementsInterface(RoutableBehavior::class)) {
            return;
        }

        $metadata = $event->getMetadata();
        $metadata->addFieldMapping(
            self::ROUTE_FIELD,
            [
                'encoding' => 'system_localized',
                'property' => self::ROUTE_FIELD,
            ]
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
