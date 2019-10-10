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

use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\VirtualProxyInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Serializer\ArticleWebsiteSubscriber;
use Sulu\Bundle\ArticleBundle\Document\Structure\ArticleBridge;
use Sulu\Bundle\ArticleBundle\Document\Structure\ContentProxyFactory;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\DocumentManager\Document\UnknownDocument;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ArticleWebsiteSubscriberTest extends TestCase
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @var ContentProxyFactory
     */
    private $contentProxyFactory;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var ArticleWebsiteSubscriber
     */
    private $subscriber;

    /**
     * @var ArticleBridge
     */
    private $structure;

    public function setUp(): void
    {
        $this->markTestSkipped('Should be refractored into own service');

        $requestAttributes = $this->prophesize(RequestAttributes::class);
        $requestAttributes->getAttribute('webspaceKey')->willReturn('sulu_io');

        $request = $this->prophesize(Request::class);
        $request->reveal()->attributes = new ParameterBag(['_sulu' => $requestAttributes->reveal()]);

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request->reveal());

        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->proxyFactory = new LazyLoadingValueHolderFactory();
        $this->contentProxyFactory = new ContentProxyFactory(
            $this->contentTypeManager->reveal(),
            $this->proxyFactory,
            $requestStack->reveal()
        );
        $this->extensionManager = $this->prophesize(ExtensionManagerInterface::class);

        $this->subscriber = new ArticleWebsiteSubscriber(
            $this->structureManager->reveal(),
            $this->extensionManager->reveal(),
            $this->contentProxyFactory
        );

        $this->structure = $this->prophesize(ArticleBridge::class);
        $this->structureManager->getStructure('default', 'article')->willReturn($this->structure->reveal());
    }

    public function testResolveContentForArticleOnPostSerialize()
    {
        $context = SerializationContext::create()
            ->setAttribute('website', true)
            ->setAttribute('pageNumber', 1);

        $children = [
            $this->prophesize(ArticlePageDocument::class),
            $this->prophesize(ArticlePageDocument::class),
        ];

        $page = 1;
        foreach ($children as $child) {
            $child->getPageNumber()->willReturn(++$page);
        }

        $object = $this->prophesize(ArticleDocument::class);
        $object->getPageNumber()->willReturn(1);
        $object->getStructureType()->willReturn('default');
        $object->getChildren()->willReturn(
            array_map(
                function ($child) {
                    return $child->reveal();
                },
                $children
            )
        );
        $this->structure->setDocument($object->reveal())->shouldBeCalled();

        $structure = $this->prophesize(Structure::class);
        $structure->toArray()->willReturn(['title' => 'Test article']);
        $object->getStructure()->willReturn($structure->reveal())->shouldBeCalled();

        $visitor = $this->prophesize(SerializationVisitorInterface::class);

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($object->reveal());
        $event->getContext()->willReturn($context);
        $event->getVisitor()->willReturn($visitor);

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'content' === $metadata->name;
        }), Argument::type(VirtualProxyInterface::class))->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'view' === $metadata->name;
        }), Argument::type(VirtualProxyInterface::class))->shouldBeCalled();

        $this->subscriber->resolveContentForArticleOnPostSerialize($event->reveal());
    }

    public function testResolveContentForArticleOnPostSerializePage2()
    {
        $context = SerializationContext::create()
            ->setAttribute('website', true)
            ->setAttribute('pageNumber', 2);

        $children = [
            $this->prophesize(ArticlePageDocument::class),
            $this->prophesize(ArticlePageDocument::class),
        ];

        $page = 1;
        foreach ($children as $child) {
            $child->getPageNumber()->willReturn(++$page);
        }

        $object = $this->prophesize(ArticleDocument::class);
        $object->getPageNumber()->willReturn(1);
        $object->getStructureType()->shouldNotBeCalled();
        $object->getChildren()->willReturn(
            array_map(
                function ($child) {
                    return $child->reveal();
                },
                $children
            )
        );

        $structure = $this->prophesize(Structure::class);
        $structure->toArray()->willReturn(['title' => 'Test article']);
        $object->getStructure()->shouldNotBeCalled();
        $children[0]->getStructure()->willReturn($structure->reveal())->shouldBeCalled();
        $children[0]->getStructureType()->willReturn('default')->shouldBeCalled();
        $this->structure->setDocument($children[0]->reveal())->shouldBeCalled();

        $visitor = $this->prophesize(SerializationVisitorInterface::class);

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($object->reveal());
        $event->getContext()->willReturn($context);
        $event->getVisitor()->willReturn($visitor);

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'content' === $metadata->name;
        }), Argument::type(VirtualProxyInterface::class))->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'view' === $metadata->name;
        }), Argument::type(VirtualProxyInterface::class))->shouldBeCalled();

        $this->subscriber->resolveContentForArticleOnPostSerialize($event->reveal());
    }

    public function testResolveContentForArticleOnPostSerializeUnknownDocument()
    {
        $context = SerializationContext::create()
            ->setAttribute('website', true)
            ->setAttribute('pageNumber', 2);

        $children = [
            $this->prophesize(UnknownDocument::class),
            $this->prophesize(ArticlePageDocument::class),
        ];

        $children[1]->getPageNumber()->willReturn(2);

        $object = $this->prophesize(ArticleDocument::class);
        $object->getPageNumber()->willReturn(1);
        $object->getStructureType()->shouldNotBeCalled();
        $object->getChildren()->willReturn(
            array_map(
                function ($child) {
                    return $child->reveal();
                },
                $children
            )
        );

        $structure = $this->prophesize(Structure::class);
        $structure->toArray()->willReturn(['title' => 'Test article']);
        $object->getStructure()->shouldNotBeCalled();
        $children[1]->getStructure()->willReturn($structure->reveal())->shouldBeCalled();
        $children[1]->getStructureType()->willReturn('default')->shouldBeCalled();
        $this->structure->setDocument($children[1]->reveal())->shouldBeCalled();

        $visitor = $this->prophesize(SerializationVisitorInterface::class);

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($object->reveal());
        $event->getContext()->willReturn($context);
        $event->getVisitor()->willReturn($visitor);

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'content' === $metadata->name;
        }), Argument::type(VirtualProxyInterface::class))->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'view' === $metadata->name;
        }), Argument::type(VirtualProxyInterface::class))->shouldBeCalled();

        $this->subscriber->resolveContentForArticleOnPostSerialize($event->reveal());
    }
}
