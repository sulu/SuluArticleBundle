<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Document\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutableBehavior;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\RoutableSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;

class RoutableSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainRouteGeneratorInterface
     */
    private $routeGeneratorPool;

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
     * @var StructureMetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var RoutableSubscriber
     */
    private $routableSubscriber;

    /**
     * @var RoutableBehavior
     */
    private $document;

    /**
     * @var NodeInterface
     */
    private $node;

    protected function setUp()
    {
        $this->routeGeneratorPool = $this->prophesize(ChainRouteGeneratorInterface::class);
        $this->routeManager = $this->prophesize(RouteManagerInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->metadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);

        $this->document = $this->prophesize(RoutableBehavior::class);

        $this->node = $this->prophesize(NodeInterface::class);
        $this->node->getIdentifier()->willReturn('123-123-123');

        $this->routableSubscriber = new RoutableSubscriber(
            $this->routeGeneratorPool->reveal(),
            $this->routeManager->reveal(),
            $this->routeRepository->reveal(),
            $this->entityManager->reveal(),
            $this->propertyEncoder->reveal(),
            $this->metadataFactory->reveal(),
            $this->documentInspector->reveal()
        );
    }

    protected function prophesizeEvent($className, $routePath = null)
    {
        $event = $this->prophesize($className);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getLocale()->willReturn('de');
        $event->getNode()->willReturn($this->node->reveal());
        $event->getOption('route_path')->willReturn($routePath);

        return $event->reveal();
    }

    public function testHandleHydrate()
    {
        $route = $this->prophesize(RouteInterface::class);
        $this->document->setRoute($route->reveal())->shouldBeCalled();
        $this->document->getOriginalLocale()->willReturn('de');
        $this->document->getStructureType()->willReturn('default');
        $this->routeRepository->findByPath('/test', 'de')->willReturn($route->reveal());

        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(RoutableSubscriber::TAG_NAME)->willReturn(false);
        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'de')
            ->willReturn('i18n:de-' . RoutableSubscriber::ROUTE_FIELD);
        $this->node->getPropertyValueWithDefault('i18n:de-' . RoutableSubscriber::ROUTE_FIELD, null)
            ->willReturn('/test');
        $this->document->getClass()->willReturn(ArticleDocument::class);
        $this->document->getUuid()->willReturn('123-123-123');

        $this->routeRepository->findByEntity(ArticleDocument::class, '123-123-123', 'de')->willReturn($route->reveal());

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'de')
            ->willReturn('i18n:de-' . RoutableSubscriber::ROUTE_FIELD);
        $this->node->getPropertyValueWithDefault('i18n:de-' . RoutableSubscriber::ROUTE_FIELD, null)
            ->willReturn('/test');

        $this->document->setRoutePath('/test')->shouldBeCalled();

        $this->routableSubscriber->handleHydrate($this->prophesizeEvent(HydrateEvent::class));
    }

    public function testHandleHydrateTaggedProperty()
    {
        $route = $this->prophesize(RouteInterface::class);
        $this->document->setRoute($route->reveal())->shouldBeCalled();
        $this->document->getUuid()->willReturn('123-123-123');
        $this->document->getOriginalLocale()->willReturn('de');
        $this->document->getStructureType()->willReturn('default');
        $this->document->getClass()->willReturn(ArticleDocument::class);

        $this->routeRepository->findByEntity(ArticleDocument::class, '123-123-123', 'de')->willReturn($route->reveal());

        $propertyMetadata = $this->prophesize(PropertyMetadata::class);
        $propertyMetadata->getName()->willReturn('test');

        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(RoutableSubscriber::TAG_NAME)->willReturn(true);
        $metadata->getPropertyByTagName(RoutableSubscriber::TAG_NAME)->willReturn($propertyMetadata->reveal());
        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $this->propertyEncoder->localizedSystemName('test', 'de')->willReturn('i18n:de-test');
        $this->node->getPropertyValueWithDefault('i18n:de-test', null)->willReturn('/test');

        $this->document->setRoutePath('/test')->shouldBeCalled();

        $this->routableSubscriber->handleHydrate($this->prophesizeEvent(HydrateEvent::class));
    }

    public function testHandlePersist()
    {
        $this->document->getRoutePath()->willReturn(null);
        $this->document->setRoutePath('/test')->shouldBeCalled();
        $this->document->setUuid('123-123-123')->shouldBeCalled();

        $this->document->getStructureType()->willReturn('default');
        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(RoutableSubscriber::TAG_NAME)->willReturn(false);
        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $this->routeGeneratorPool->generate($this->document->reveal(), null)
            ->willReturn(new Route('/test', null, get_class($this->document->reveal())));

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'de')
            ->willReturn('i18n:de-routePath');
        $this->node->setProperty('i18n:de-routePath', '/test')->shouldBeCalled();

        $this->routeManager->create(Argument::cetera())->shouldNotBeCalled();
        $this->entityManager->persist(Argument::cetera())->shouldNotBeCalled();
        $this->entityManager->flush()->shouldNotBeCalled();

        $this->routableSubscriber->handlePersist($this->prophesizeEvent(PersistEvent::class));
    }

    public function testHandlePersistWithRoute()
    {
        $this->document->getRoutePath()->willReturn(null);
        $this->document->setRoutePath('/test-1')->shouldBeCalled();
        $this->document->setUuid('123-123-123')->shouldBeCalled();

        $this->document->getStructureType()->willReturn('default');
        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(RoutableSubscriber::TAG_NAME)->willReturn(false);
        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $this->routeGeneratorPool->generate($this->document->reveal(), '/test-1')
            ->willReturn(new Route('/test-1', null, get_class($this->document->reveal())));

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'de')
            ->willReturn('i18n:de-routePath');
        $this->node->setProperty('i18n:de-routePath', '/test-1')->shouldBeCalled();

        $this->routeManager->create(Argument::cetera())->shouldNotBeCalled();
        $this->entityManager->persist(Argument::cetera())->shouldNotBeCalled();
        $this->entityManager->flush()->shouldNotBeCalled();

        $this->routableSubscriber->handlePersist($this->prophesizeEvent(PersistEvent::class, '/test-1'));
    }

    public function testHandlePersistUpdate()
    {
        $this->document->getRoutePath()->willReturn('/test-1');
        $this->document->setUuid('123-123-123')->shouldBeCalled();
        $this->document->setRoutePath('/test-2')->shouldBeCalled();

        $this->document->getStructureType()->willReturn('default');
        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(RoutableSubscriber::TAG_NAME)->willReturn(false);
        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $this->routeGeneratorPool->generate($this->document->reveal(), '/test-2')
            ->willReturn(new Route('/test-2', null, get_class($this->document->reveal())));

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'de')
            ->willReturn('i18n:de-routePath');
        $this->node->setProperty('i18n:de-routePath', '/test-2')->shouldBeCalled();

        $this->routeManager->create(Argument::cetera())->shouldNotBeCalled();
        $this->entityManager->persist(Argument::cetera())->shouldNotBeCalled();
        $this->entityManager->flush()->shouldNotBeCalled();

        $this->routableSubscriber->handlePersist($this->prophesizeEvent(PersistEvent::class, '/test-2'));
    }

    public function testHandleRemove()
    {
        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $route = $this->prophesize(RouteInterface::class);
        $this->document->getRoutePath()->willReturn('/test');
        $this->document->getOriginalLocale()->willReturn('de');
        $this->routeRepository->findByPath('/test', 'de')->willReturn($route->reveal());

        $this->entityManager->remove($route->reveal())->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $this->routableSubscriber->handleRemove($event->reveal());
    }

    public function testHandleRemoveWithChildren()
    {
        $this->document->willImplement(ChildrenBehavior::class);

        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->document->getRoutePath()->willReturn('/test');
        $this->document->getOriginalLocale()->willReturn('de');
        $route1 = $this->prophesize(RouteInterface::class);
        $this->routeRepository->findByPath('/test', 'de')->willReturn($route1->reveal());

        $child = $this->prophesize(RoutableBehavior::class);
        $child->willImplement(ChildrenBehavior::class);
        $child->getChildren()->willReturn([]);
        $this->document->getChildren()->willReturn([$child->reveal()]);

        $child->getRoutePath()->willReturn('/test/test-2');
        $child->getOriginalLocale()->willReturn('de');
        $route2 = $this->prophesize(RouteInterface::class);
        $this->routeRepository->findByPath('/test/test-2', 'de')->willReturn($route2->reveal());

        $this->entityManager->remove($route1->reveal())->shouldBeCalled();
        $this->entityManager->remove($route2->reveal())->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $this->routableSubscriber->handleRemove($event->reveal());
    }
}
