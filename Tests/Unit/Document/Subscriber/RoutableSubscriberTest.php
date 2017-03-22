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
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;

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
     * @var RoutableSubscriber
     */
    private $articleSubscriber;

    /**
     * @var RoutableBehavior
     */
    private $document;

    protected function setUp()
    {
        $this->routeManager = $this->prophesize(RouteManagerInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);

        $this->document = $this->prophesize(RoutableBehavior::class);

        $this->articleSubscriber = new RoutableSubscriber(
            $this->routeManager->reveal(),
            $this->routeRepository->reveal(),
            $this->entityManager->reveal()
        );
    }

    protected function prophesizeEvent($className, $routePath = null)
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getIdentifier()->willReturn('123-123-123');

        $event = $this->prophesize($className);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getLocale()->willReturn('de');
        $event->getNode()->willReturn($node->reveal());
        $event->getOption('route_path')->willReturn($routePath);

        return $event->reveal();
    }

    public function testHandleHydrate()
    {
        $route = $this->prophesize(RouteInterface::class);
        $this->document->getRoutePath()->willReturn('/test');
        $this->document->setRoute($route->reveal())->shouldBeCalled();
        $this->document->getOriginalLocale()->willReturn('de');
        $this->routeRepository->findByPath('/test', 'de')->willReturn($route->reveal());

        $this->articleSubscriber->handleHydrate($this->prophesizeEvent(HydrateEvent::class));
    }

    public function testHandleRoute()
    {
        $route = $this->prophesize(RouteInterface::class);
        $this->document->getRoutePath()->willReturn(null);
        $this->document->setUuid('123-123-123')->shouldBeCalled();
        $this->routeManager->create($this->document->reveal(), null)->shouldBeCalled()->willReturn($route->reveal());

        $this->entityManager->persist($route->reveal())->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $this->articleSubscriber->handleRoute($this->prophesizeEvent(PersistEvent::class));
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

        $this->articleSubscriber->handleRoute($this->prophesizeEvent(PersistEvent::class, '/test-1'));
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

        $this->articleSubscriber->handleRouteUpdate($this->prophesizeEvent(PersistEvent::class, '/test-2'));
    }
}
