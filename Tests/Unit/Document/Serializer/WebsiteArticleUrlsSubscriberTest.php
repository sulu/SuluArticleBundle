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
use JMS\Serializer\SerializationContext;
use PhpCollection\Map;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Serializer\WebsiteArticleUrlsSubscriber;
use Sulu\Bundle\RouteBundle\Entity\RouteRepository;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Serializer\ArraySerializationVisitor;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WebsiteArticleUrlsSubscriberTest extends \PHPUnit_Framework_TestCase
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
     * @var WebsiteArticleUrlsSubscriber
     */
    private $urlsSubscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->routeRepository = $this->prophesize(RouteRepository::class);

        $this->urlsSubscriber = new WebsiteArticleUrlsSubscriber(
            $this->requestStack->reveal(),
            $this->routeRepository->reveal()
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
        $visitor = $this->prophesize(ArraySerializationVisitor::class);
        $context = $this->prophesize(SerializationContext::class);

        $entityId = '123-123-123';
        $article->getUuid()->willReturn($entityId);

        $contextAttributes = $this->prophesize(Map::class);
        $contextAttributes->containsKey('website')->willReturn(true);
        $context->reveal()->attributes = $contextAttributes->reveal();

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($article->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());
        $event->getContext()->willReturn($context->reveal());

        $expected = ['de' => '/seite', 'en' => '/page'];

        $entityClass = get_class($article->reveal());
        foreach ($expected as $locale => $path) {
            $route = $this->prophesize(RouteInterface::class);
            $route->getPath()->willReturn($path);

            $this->routeRepository->findByEntity($entityClass, $entityId, $locale)->willReturn($route->reveal());
        }

        $context->accept($expected)->willReturn($expected)->shouldBeCalled();
        $visitor->addData('urls', $expected)->shouldBeCalled();

        $this->urlsSubscriber->addUrlsOnPostSerialize($event->reveal());
    }
}
