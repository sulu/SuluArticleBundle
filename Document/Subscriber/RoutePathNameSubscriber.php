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

use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutableBehavior;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Writes the routePathName property on article PHPCR nodes.
 *
 * This property stores the encoded name of the routePath property so that
 * the PHPCR migration bundle can resolve the correct route URL for each article.
 */
class RoutePathNameSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PropertyEncoder $propertyEncoder,
        private StructureMetadataFactoryInterface $metadataFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => ['handlePersist', -2048],
            Events::PUBLISH => ['handlePublish', -2048],
        ];
    }

    public function handlePersist(AbstractMappingEvent $event): void
    {
        $this->writeRoutePathName($event);
    }

    public function handlePublish(AbstractMappingEvent $event): void
    {
        $this->writeRoutePathName($event);
    }

    private function writeRoutePathName(AbstractMappingEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof RoutableBehavior) {
            return;
        }

        $locale = $event->getLocale();
        $routePathPropertyName = $this->getRoutePathPropertyName((string) $document->getStructureType(), $locale);

        $event->getNode()->setProperty(
            $this->propertyEncoder->localizedContentName(RoutableSubscriber::ROUTE_FIELD_NAME, $locale),
            $routePathPropertyName,
        );
    }

    private function getRoutePathPropertyName(string $structureType, string $locale): string
    {
        $metadata = $this->metadataFactory->getStructureMetadata('article', $structureType);

        if (null !== $metadata && $metadata->hasTag(RoutableSubscriber::TAG_NAME)) {
            /** @var string $name */
            $name = $metadata->getPropertyByTagName(RoutableSubscriber::TAG_NAME)->getName();

            return $this->propertyEncoder->localizedSystemName($name, $locale);
        }

        return $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, $locale);
    }
}
