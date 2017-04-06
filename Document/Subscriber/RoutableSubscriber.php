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
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutableBehavior;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events to create/update/remove routes.
 */
class RoutableSubscriber implements EventSubscriberInterface
{
    const FIELD = 'routePath';

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
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @param RouteManagerInterface $routeManager
     * @param RouteRepositoryInterface $routeRepository
     * @param EntityManagerInterface $entityManager
     * @param PropertyEncoder $propertyEncoder
     */
    public function __construct(
        RouteManagerInterface $routeManager,
        RouteRepositoryInterface $routeRepository,
        EntityManagerInterface $entityManager,
        PropertyEncoder $propertyEncoder
    ) {
        $this->routeManager = $routeManager;
        $this->routeRepository = $routeRepository;
        $this->entityManager = $entityManager;
        $this->propertyEncoder = $propertyEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => ['handleHydrate'],
            // low priority because all other subscriber should be finished
            Events::PERSIST => [['handleRouteUpdate', -2000], ['handleRoute', -2010], ['persistRoute', -2020]],
            Events::REMOVE => [
                // high priority to ensure nodes are not deleted until we iterate over children
                ['handleRemove', 1024],
            ],
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
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        $routePath = $event->getNode()->getPropertyValueWithDefault($this->getPropertyName($event->getLocale()), null);
        if (!$routePath) {
            return;
        }

        $route = $this->routeRepository->findByPath($routePath, $event->getLocale());
        if (!$route) {
            return;
        }

        $document->setRoutePath($routePath);
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
     * Save route-path to node.
     *
     * @param PersistEvent $event
     */
    public function persistRoute(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior || null === $document->getRoute()) {
            return;
        }

        $node = $event->getNode();
        $node->setProperty($this->getPropertyName($event->getLocale()), $document->getRoutePath());
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
            'routePath',
            [
                'encoding' => 'system_localized',
                'property' => 'routePath',
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

    /**
     * Returns encoded property-name.
     *
     * @param string $locale
     *
     * @return string
     */
    private function getPropertyName($locale)
    {
        return $this->propertyEncoder->localizedSystemName(self::FIELD, $locale);
    }
}
