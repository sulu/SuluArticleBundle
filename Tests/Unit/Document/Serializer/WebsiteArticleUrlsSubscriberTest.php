<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Document\Serializer;

use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Serializer\WebsiteArticleUrlsSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\RouteBundle\Entity\RouteRepository;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WebsiteArticleUrlsSubscriberTest extends TestCase
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var WebsiteArticleUrlsSubscriber
     */
    private $urlsSubscriber;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->routeRepository = $this->prophesize(RouteRepository::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);

        $this->urlsSubscriber = new WebsiteArticleUrlsSubscriber(
            $this->requestStack->reveal(),
            $this->routeRepository->reveal(),
            $this->webspaceManager->reveal(),
            $this->documentInspector->reveal()
        );

        $webspace = new Webspace();
        $webspace->addLocalization(new Localization('en'));
        $webspace->addLocalization(new Localization('de'));

        $request = $this->prophesize(Request::class);
        $request->get('_sulu')->willReturn(new RequestAttributes(['webspace' => $webspace]));
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
    }

    public function testAddUrlsOnPostSerialize()
    {
        $article = $this->prophesize(ArticleDocument::class);
        $visitor = $this->prophesize(SerializationVisitorInterface::class);

        $context = $this->prophesize(SerializationContext::class);
        $context->hasAttribute('urls')->willReturn(true);

        $entityId = '123-123-123';
        $article->getUuid()->willReturn($entityId);

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($article->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());
        $event->getContext()->willReturn($context->reveal());

        $entityClass = get_class($article->reveal());

        $this->documentInspector->getPublishedLocales($article->reveal())->willReturn(['en', 'de']);

        $deRoute = $this->prophesize(RouteInterface::class);
        $deRoute->getPath()->willReturn('/seite');
        $this->routeRepository->findByEntity($entityClass, $entityId, 'de')->willReturn($deRoute->reveal());
        $this->webspaceManager->findUrlByResourceLocator('/seite', null, 'de')->willReturn('http://sulu.io/de/seite');

        $enRoute = $this->prophesize(RouteInterface::class);
        $enRoute->getPath()->willReturn('/page');
        $this->routeRepository->findByEntity($entityClass, $entityId, 'en')->willReturn($enRoute->reveal());
        $this->webspaceManager->findUrlByResourceLocator('/page', null, 'en')->willReturn('http://sulu.io/page');

        $visitor->visitProperty(
            Argument::that(function(StaticPropertyMetadata $metadata) {
                return 'urls' === $metadata->name;
            }),
            ['de' => '/seite', 'en' => '/page']
        )->shouldBeCalled();

        $visitor->visitProperty(
            Argument::that(function(StaticPropertyMetadata $metadata) {
                return 'localizations' === $metadata->name;
            }),
            [
                'de' => ['locale' => 'de', 'url' => 'http://sulu.io/de/seite', 'alternate' => true],
                'en' => ['locale' => 'en', 'url' => 'http://sulu.io/page', 'alternate' => true],
            ]
        )->shouldBeCalled();

        $this->urlsSubscriber->addUrlsOnPostSerialize($event->reveal());
    }

    public function testAddUrlsOnPostSerializeNonExistingRoute()
    {
        $article = $this->prophesize(ArticleDocument::class);
        $visitor = $this->prophesize(SerializationVisitorInterface::class);

        $context = $this->prophesize(SerializationContext::class);
        $context->hasAttribute('urls')->willReturn(true);

        $entityId = '123-123-123';
        $article->getUuid()->willReturn($entityId);

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($article->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());
        $event->getContext()->willReturn($context->reveal());

        $entityClass = get_class($article->reveal());

        $this->documentInspector->getPublishedLocales($article->reveal())->willReturn(['en', 'de']);

        $deRoute = $this->prophesize(RouteInterface::class);
        $deRoute->getPath()->willReturn('/seite');
        $this->routeRepository->findByEntity($entityClass, $entityId, 'de')->willReturn($deRoute->reveal());
        $this->webspaceManager->findUrlByResourceLocator('/seite', null, 'de')->willReturn('http://sulu.io/de/seite');

        $enRoute = $this->prophesize(RouteInterface::class);
        $enRoute->getPath()->willReturn('/page');
        $this->routeRepository->findByEntity($entityClass, $entityId, 'en')->willReturn(null);
        $this->webspaceManager->findUrlByResourceLocator('/', null, 'en')->willReturn('http://sulu.io/');

        $visitor->visitProperty(
            Argument::that(function(StaticPropertyMetadata $metadata) {
                return 'urls' === $metadata->name;
            }),
            ['de' => '/seite', 'en' => '/']
        )->shouldBeCalled();

        $visitor->visitProperty(
            Argument::that(function(StaticPropertyMetadata $metadata) {
                return 'localizations' === $metadata->name;
            }),
            [
                'de' => ['locale' => 'de', 'url' => 'http://sulu.io/de/seite', 'alternate' => true],
                'en' => ['locale' => 'en', 'url' => 'http://sulu.io/', 'alternate' => false],
            ]
        )->shouldBeCalled();

        $this->urlsSubscriber->addUrlsOnPostSerialize($event->reveal());
    }

    public function testAddUrlsOnPostSerializeUnpublishedLocale()
    {
        $article = $this->prophesize(ArticleDocument::class);
        $visitor = $this->prophesize(SerializationVisitorInterface::class);

        $context = $this->prophesize(SerializationContext::class);
        $context->hasAttribute('urls')->willReturn(true);

        $entityId = '123-123-123';
        $article->getUuid()->willReturn($entityId);

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($article->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());
        $event->getContext()->willReturn($context->reveal());

        $entityClass = get_class($article->reveal());

        $this->documentInspector->getPublishedLocales($article->reveal())->willReturn(['de']);

        $deRoute = $this->prophesize(RouteInterface::class);
        $deRoute->getPath()->willReturn('/seite');
        $this->routeRepository->findByEntity($entityClass, $entityId, 'de')->willReturn($deRoute->reveal());
        $this->webspaceManager->findUrlByResourceLocator('/seite', null, 'de')->willReturn('http://sulu.io/de/seite');

        $this->routeRepository->findByEntity(Argument::any())->shouldNotBeCalled();
        $this->webspaceManager->findUrlByResourceLocator('/', null, 'en')->willReturn('http://sulu.io/');

        $visitor->visitProperty(
            Argument::that(function(StaticPropertyMetadata $metadata) {
                return 'urls' === $metadata->name;
            }),
            ['de' => '/seite', 'en' => '/']
        )->shouldBeCalled();

        $visitor->visitProperty(
            Argument::that(function(StaticPropertyMetadata $metadata) {
                return 'localizations' === $metadata->name;
            }),
            [
                'de' => ['locale' => 'de', 'url' => 'http://sulu.io/de/seite', 'alternate' => true],
                'en' => ['locale' => 'en', 'url' => 'http://sulu.io/', 'alternate' => false],
            ]
        )->shouldBeCalled();

        $this->urlsSubscriber->addUrlsOnPostSerialize($event->reveal());
    }
}
