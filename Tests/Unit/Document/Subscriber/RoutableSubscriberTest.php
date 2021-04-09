<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutablePageBehavior;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\RoutableSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;

class RoutableSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainGenerator;

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
     * @var StructureMetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

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
        $this->chainGenerator = $this->prophesize(ChainRouteGeneratorInterface::class);
        $this->routeManager = $this->prophesize(RouteManagerInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->metadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->conflictResolver = $this->prophesize(ConflictResolverInterface::class);

        $this->document = $this->prophesize(RoutableBehavior::class);

        $this->node = $this->prophesize(NodeInterface::class);
        $this->node->getIdentifier()->willReturn('123-123-123');

        $this->conflictResolver->resolve(Argument::type(RouteInterface::class))->will(
            function(array $arguments) {
                return $arguments[0];
            }
        );

        $this->routableSubscriber = new RoutableSubscriber(
            $this->chainGenerator->reveal(),
            $this->routeManager->reveal(),
            $this->routeRepository->reveal(),
            $this->entityManager->reveal(),
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->propertyEncoder->reveal(),
            $this->metadataFactory->reveal(),
            $this->conflictResolver->reveal()
        );
    }

    protected function prophesizeEvent($className)
    {
        $event = $this->prophesize($className);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getLocale()->willReturn('de');
        $event->getNode()->willReturn($this->node->reveal());

        return $event->reveal();
    }

    public function testHandleHydrate()
    {
        $route = $this->prophesize(RouteInterface::class);
        $this->document->setRoute($route->reveal())->shouldBeCalled();
        $this->document->getLocale()->willReturn('de');
        $this->document->getStructureType()->willReturn('default');
        $this->routeRepository->findByPath('/test', 'de')->willReturn($route->reveal());

        $this->documentInspector->getLocalizationState($this->document)->willReturn(LocalizationState::LOCALIZED);

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

    public function testHandleHydrateShadow()
    {
        $route = $this->prophesize(RouteInterface::class);
        $this->document->setRoute($route->reveal())->shouldBeCalled();
        $this->document->getLocale()->willReturn('de');
        $this->document->getOriginalLocale()->willReturn('en');
        $this->document->getStructureType()->willReturn('default');
        $this->routeRepository->findByPath('/test', 'en')->willReturn($route->reveal());

        $this->documentInspector->getLocalizationState($this->document)->willReturn(LocalizationState::SHADOW);

        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(RoutableSubscriber::TAG_NAME)->willReturn(false);
        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'en')
            ->willReturn('i18n:en-' . RoutableSubscriber::ROUTE_FIELD);
        $this->node->getPropertyValueWithDefault('i18n:de-' . RoutableSubscriber::ROUTE_FIELD, null)
            ->willReturn('/test');
        $this->document->getClass()->willReturn(ArticleDocument::class);
        $this->document->getUuid()->willReturn('123-123-123');

        $this->routeRepository->findByEntity(ArticleDocument::class, '123-123-123', 'en')->willReturn($route->reveal());

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'en')
            ->willReturn('i18n:de-' . RoutableSubscriber::ROUTE_FIELD);
        $this->node->getPropertyValueWithDefault('i18n:en-' . RoutableSubscriber::ROUTE_FIELD, null)
            ->willReturn('/test');

        $this->document->setRoutePath('/test')->shouldBeCalled();

        $this->routableSubscriber->handleHydrate($this->prophesizeEvent(HydrateEvent::class));
    }

    public function testHandleHydrateTaggedProperty()
    {
        $route = $this->prophesize(RouteInterface::class);
        $this->document->setRoute($route->reveal())->shouldBeCalled();
        $this->document->getUuid()->willReturn('123-123-123');
        $this->document->getLocale()->willReturn('de');
        $this->document->getStructureType()->willReturn('default');
        $this->document->getClass()->willReturn(ArticleDocument::class);

        $this->documentInspector->getLocalizationState($this->document)->willReturn(LocalizationState::LOCALIZED);

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
        $this->document->setRoutePath('/test')->shouldBeCalled();
        $this->document->setUuid('123-123-123')->shouldBeCalled();

        $this->document->getStructureType()->willReturn('default');
        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(RoutableSubscriber::TAG_NAME)->willReturn(false);
        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $this->chainGenerator->generate($this->document->reveal(), null)
            ->willReturn(new Route('/test', null, get_class($this->document->reveal())));

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'de')
            ->willReturn('i18n:de-routePath');
        $this->node->getPropertyValueWithDefault('i18n:de-routePath', null)->willReturn(null);
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

        $this->chainGenerator->generate($this->document->reveal(), '/test-1')
            ->willReturn(new Route('/test-1', null, get_class($this->document->reveal())));

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'de')
            ->willReturn('i18n:de-routePath');
        $this->node->getPropertyValueWithDefault('i18n:de-routePath', null)->willReturn('/test-1');
        $this->node->setProperty('i18n:de-routePath', '/test-1')->shouldBeCalled();

        $this->routeManager->create(Argument::cetera())->shouldNotBeCalled();
        $this->entityManager->persist(Argument::cetera())->shouldNotBeCalled();
        $this->entityManager->flush()->shouldNotBeCalled();

        $this->routableSubscriber->handlePersist($this->prophesizeEvent(PersistEvent::class));
    }

    public function testHandlePersistUpdate()
    {
        $this->document->setUuid('123-123-123')->shouldBeCalled();
        $this->document->setRoutePath('/test-2')->shouldBeCalled();

        $this->document->getStructureType()->willReturn('default');
        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(RoutableSubscriber::TAG_NAME)->willReturn(false);
        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $this->chainGenerator->generate($this->document->reveal(), '/test-2')
            ->willReturn(new Route('/test-2', null, get_class($this->document->reveal())));

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'de')
            ->willReturn('i18n:de-routePath');
        $this->node->getPropertyValueWithDefault('i18n:de-routePath', null)->willReturn('/test-2');
        $this->node->setProperty('i18n:de-routePath', '/test-2')->shouldBeCalled();

        $this->routeManager->create(Argument::cetera())->shouldNotBeCalled();
        $this->entityManager->persist(Argument::cetera())->shouldNotBeCalled();
        $this->entityManager->flush()->shouldNotBeCalled();

        $this->routableSubscriber->handlePersist($this->prophesizeEvent(PersistEvent::class));
    }

    public function testHandleRemove()
    {
        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de', 'en']);

        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->document->getUuid()->willReturn('123-123-123');
        $this->document->getRoutePath()->willReturn('/test');
        $this->document->getClass()->willReturn(ArticleDocument::class);
        $this->document->getOriginalLocale()->willReturn('de');

        $this->documentManager->find('123-123-123', 'de')->willReturn($this->document->reveal());
        $this->documentManager->find('123-123-123', 'en')->willReturn($this->document->reveal());

        $routeDE = $this->prophesize(RouteInterface::class);
        $routeEN = $this->prophesize(RouteInterface::class);
        $this->routeRepository->findByEntity(ArticleDocument::class, '123-123-123', 'de')
            ->willReturn($routeDE->reveal());
        $this->routeRepository->findByEntity(ArticleDocument::class, '123-123-123', 'en')
            ->willReturn($routeEN->reveal());

        $this->entityManager->remove($routeDE->reveal())->shouldBeCalled();
        $this->entityManager->remove($routeEN->reveal())->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $this->routableSubscriber->handleRemove($event->reveal());
    }

    public function testHandleRemoveWithChildren()
    {
        $this->document->willImplement(ChildrenBehavior::class);

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de']);

        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $this->document->getUuid()->willReturn('123-123-123');
        $this->document->getRoutePath()->willReturn('/test');
        $this->document->getOriginalLocale()->willReturn('de');
        $this->document->getClass()->willReturn(ArticleDocument::class);
        $route1 = $this->prophesize(RouteInterface::class);
        $this->routeRepository->findByEntity(ArticleDocument::class, '123-123-123', 'de')
            ->willReturn($route1->reveal());

        $this->documentManager->find('123-123-123', 'de')->willReturn($this->document->reveal());

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

    public function testHandleCopy()
    {
        $event = $this->prophesize(CopyEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getCopiedPath()->willReturn('/cmf/articles/2017/04/test-article');

        $this->documentInspector->getLocales($this->document->reveal())->willReturn(['de']);
        $this->documentManager->find('/cmf/articles/2017/04/test-article', 'de')->willReturn($this->document->reveal());

        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test');

        $this->chainGenerator->generate($this->document->reveal())->willReturn($route->reveal());

        $this->document->getStructureType()->willReturn('default');
        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(RoutableSubscriber::TAG_NAME)->willReturn(false);
        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $this->propertyEncoder->localizedSystemName(RoutableSubscriber::ROUTE_FIELD, 'de')
            ->willReturn('i18n:de-routePath');
        $this->node->setProperty('i18n:de-routePath', '/test')->shouldBeCalled();
        $this->document->setRoutePath('/test')->shouldBeCalled();

        $this->documentInspector->getNode($this->document->reveal())->willReturn($this->node->reveal());

        $this->routableSubscriber->handleCopy($event->reveal());
    }

    public function testHandleReorder()
    {
        $this->document->willImplement(ParentBehavior::class);

        $event = $this->prophesize(ReorderEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $parentDocument = $this->prophesize(ChildrenBehavior::class);
        $parentDocument->willImplement(StructureBehavior::class);
        $this->document->getParent()->willReturn($parentDocument->reveal());
        $this->documentInspector->getLocale($parentDocument)->willReturn('de');

        $parentDocument->getStructureType()->willReturn('default');

        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(Argument::any())->willReturn(false);

        $this->metadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());
        $this->propertyEncoder->localizedSystemName('routePath', 'de')->willReturn('i18n:de-routePath');

        $children = [
            $this->prophesize(RoutablePageBehavior::class),
            $this->prophesize(RoutablePageBehavior::class),
            $this->prophesize(RoutablePageBehavior::class),
        ];
        $parentDocument->getChildren()
            ->willReturn([$children[0]->reveal(), $children[1]->reveal(), $children[2]->reveal()]);

        $nodes = [
            $this->prophesize(NodeInterface::class),
            $this->prophesize(NodeInterface::class),
            $this->prophesize(NodeInterface::class),
        ];
        $this->documentInspector->getNode($children[0]->reveal())->willReturn($nodes[0]->reveal());
        $this->documentInspector->getNode($children[1]->reveal())->willReturn($nodes[1]->reveal());
        $this->documentInspector->getNode($children[2]->reveal())->willReturn($nodes[2]->reveal());

        $routes = [
            $this->prophesize(RouteInterface::class),
            $this->prophesize(RouteInterface::class),
            $this->prophesize(RouteInterface::class),
        ];
        $routes[0]->getPath()->willReturn('/test-1');
        $routes[1]->getPath()->willReturn('/test-2');
        $routes[2]->getPath()->willReturn('/test-3');
        $this->chainGenerator->generate($children[0]->reveal())->willReturn($routes[0]->reveal());
        $this->chainGenerator->generate($children[1]->reveal())->willReturn($routes[1]->reveal());
        $this->chainGenerator->generate($children[2]->reveal())->willReturn($routes[2]->reveal());

        $children[0]->setRoutePath('/test-1')->shouldBeCalled();
        $children[1]->setRoutePath('/test-2')->shouldBeCalled();
        $children[2]->setRoutePath('/test-3')->shouldBeCalled();

        $nodes[0]->setProperty('i18n:de-routePath', '/test-1')->shouldBeCalled();
        $nodes[1]->setProperty('i18n:de-routePath', '/test-2')->shouldBeCalled();
        $nodes[2]->setProperty('i18n:de-routePath', '/test-3')->shouldBeCalled();

        $this->routableSubscriber->handleReorder($event->reveal());
    }
}
