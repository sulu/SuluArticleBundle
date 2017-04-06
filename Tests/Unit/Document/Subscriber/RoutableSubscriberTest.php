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
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutableBehavior;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\RoutableSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;

class RoutableSubscriberTest extends \PHPUnit_Framework_TestCase
{
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
        $this->routeManager = $this->prophesize(RouteManagerInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);

        $this->document = $this->prophesize(RoutableBehavior::class);

        $this->node = $this->prophesize(NodeInterface::class);
        $this->node->getIdentifier()->willReturn('123-123-123');

        $this->routableSubscriber = new RoutableSubscriber(
            $this->routeManager->reveal(),
            $this->routeRepository->reveal(),
            $this->entityManager->reveal(),
            $this->propertyEncoder->reveal()
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
        $this->routeRepository->findByPath('/test', 'de')->willReturn($route->reveal());

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::FIELD, 'de')
            ->willReturn('i18n:de-' . RoutableSubscriber::FIELD);
        $this->node->getPropertyValueWithDefault('i18n:de-' . RoutableSubscriber::FIELD, null)->willReturn('/test');

        $this->document->setRoutePath('/test')->shouldBeCalled();

        $this->routableSubscriber->handleHydrate($this->prophesizeEvent(HydrateEvent::class));
    }

    public function testHandleRoute()
    {
        $route = $this->prophesize(RouteInterface::class);
        $this->document->getRoutePath()->willReturn(null);
        $this->document->setUuid('123-123-123')->shouldBeCalled();
        $this->routeManager->create($this->document->reveal(), null)->shouldBeCalled()->willReturn($route->reveal());

        $this->entityManager->persist($route->reveal())->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $this->routableSubscriber->handleRoute($this->prophesizeEvent(PersistEvent::class));
    }

    public function testHandleRouteWithRoute()
    {
        $route = $this->prophesize(RouteInterface::class);
        $this->document->getRoutePath()->willReturn(null);
        $this->document->setUuid('123-123-123')->shouldBeCalled();
        $this->routeManager->create($this->document->reveal(), '/test-1')
            ->shouldBeCalled()
            ->willReturn($route->reveal());

        $this->entityManager->persist($route->reveal())->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $this->routableSubscriber->handleRoute($this->prophesizeEvent(PersistEvent::class, '/test-1'));
    }

    public function testHandleRouteUpdate()
    {
        $route = $this->prophesize(RouteInterface::class);
        $newRoute = $this->prophesize(RouteInterface::class);
        $this->document->getRoute()->willReturn($route->reveal());

        $newRoute->getPath()->willReturn('/test-2');

        $this->routeManager->update($this->document->reveal(), '/test-2')->willReturn($newRoute->reveal());
        $this->document->setRoutePath('/test-2')->shouldBeCalled();
        $this->entityManager->persist($newRoute)->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $this->routableSubscriber->handleRouteUpdate($this->prophesizeEvent(PersistEvent::class, '/test-2'));
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
