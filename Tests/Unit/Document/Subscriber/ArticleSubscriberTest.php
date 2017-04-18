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

use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\ArticleSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;

class ArticleSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var IndexerInterface
     */
    private $liveIndexer;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var ArticleSubscriber
     */
    private $articleSubscriber;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var ArticleDocument
     */
    private $document;

    /**
     * @var string
     */
    private $uuid = '123-123-123';

    /**
     * @var string
     */
    private $locale = 'de';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->indexer = $this->prophesize(IndexerInterface::class);
        $this->liveIndexer = $this->prophesize(IndexerInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);

        $this->document = $this->prophesize(ArticleDocument::class);
        $this->document->getUuid()->willReturn($this->uuid);
        $this->document->getLocale()->willReturn($this->locale);
        $this->documentManager->find($this->uuid, $this->locale)->willReturn($this->document->reveal());

        $this->articleSubscriber = new ArticleSubscriber(
            $this->indexer->reveal(),
            $this->liveIndexer->reveal(),
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->propertyEncoder->reveal()
        );
    }

    protected function prophesizeEvent($className, $locale = null, $options = null)
    {
        $event = $this->prophesize($className);
        $event->getDocument()->willReturn($this->document->reveal());

        if (null !== $options) {
            $event->getOptions()->willReturn($options);
        }

        if (null !== $locale) {
            $event->getLocale()->willReturn($locale);
        }

        return $event->reveal();
    }

    public function testHandleScheduleIndex()
    {
        $event = $this->prophesizeEvent(AbstractMappingEvent::class);
        $this->articleSubscriber->handleScheduleIndex($event);

        $this->indexer->index(Argument::any())->shouldNotBeCalled();
        $this->indexer->flush()->shouldNotBeCalled();
        $this->liveIndexer->index(Argument::any())->shouldNotBeCalled();
        $this->liveIndexer->flush()->shouldNotBeCalled();
    }

    public function testHandleScheduleIndexLive()
    {
        $event = $this->prophesizeEvent(AbstractMappingEvent::class);
        $this->articleSubscriber->handleScheduleIndexLive($event);

        $this->indexer->index(Argument::any())->shouldNotBeCalled();
        $this->indexer->flush()->shouldNotBeCalled();
        $this->liveIndexer->index(Argument::any())->shouldNotBeCalled();
        $this->liveIndexer->flush()->shouldNotBeCalled();
    }

    public function testHandleFlush()
    {
        $event = $this->prophesizeEvent(AbstractMappingEvent::class);
        $this->articleSubscriber->handleScheduleIndex($event);

        $this->documentManager->find($this->uuid, $this->locale)->willReturn($this->document->reveal());
        $this->documentManager->refresh($this->document->reveal(), $this->locale)->willReturn($this->document->reveal());

        $this->articleSubscriber->handleFlush($this->prophesize(FlushEvent::class)->reveal());

        $this->indexer->index($this->document->reveal())->shouldBeCalled();
        $this->indexer->flush()->shouldBeCalled();
        $this->liveIndexer->index(Argument::any())->shouldNotBeCalled();
        $this->liveIndexer->flush()->shouldNotBeCalled();
    }

    public function testHandleFlushLive()
    {
        $event = $this->prophesizeEvent(AbstractMappingEvent::class);
        $this->articleSubscriber->handleScheduleIndexLive($event);

        $this->documentManager->find($this->uuid, $this->locale)->willReturn($this->document->reveal());
        $this->documentManager->refresh($this->document->reveal(), $this->locale)->willReturn($this->document->reveal());

        $this->articleSubscriber->handleFlushLive($this->prophesize(FlushEvent::class)->reveal());

        $this->indexer->index(Argument::any())->shouldNotBeCalled();
        $this->indexer->flush()->shouldNotBeCalled();
        $this->liveIndexer->index($this->document->reveal())->shouldBeCalled();
        $this->liveIndexer->flush()->shouldBeCalled();
    }

    public function testHandleRemove()
    {
        $this->articleSubscriber->handleRemove($this->prophesizeEvent(RemoveEvent::class));

        $this->indexer->remove($this->document->reveal())->shouldBeCalled();
        $this->indexer->flush()->shouldBeCalled();
        $this->liveIndexer->index(Argument::any())->shouldNotBeCalled();
        $this->liveIndexer->flush()->shouldNotBeCalled();
    }

    public function testHandleRemoveLive()
    {
        $this->articleSubscriber->handleRemoveLive($this->prophesizeEvent(RemoveEvent::class));

        $this->indexer->remove(Argument::any())->shouldNotBeCalled();
        $this->indexer->flush()->shouldNotBeCalled();
        $this->liveIndexer->remove($this->document->reveal())->shouldBeCalled();
        $this->liveIndexer->flush()->shouldBeCalled();
    }

    public function testPublishChildren()
    {
        $children = [
            $this->prophesize(ArticlePageDocument::class)->reveal(),
            $this->prophesize(ArticlePageDocument::class)->reveal(),
            $this->prophesize(ArticlePageDocument::class)->reveal(),
        ];

        $this->document->getChildren()->willReturn(new \ArrayIterator($children));

        foreach ($children as $child) {
            $this->documentInspector->getLocalizationState($child)->willReturn(LocalizationState::LOCALIZED);
            $this->documentManager->publish($child, $this->locale)->shouldBeCalled();
        }

        $this->articleSubscriber->publishChildren($this->prophesizeEvent(PublishEvent::class, $this->locale));
    }

    public function testSynchronizeChildren()
    {
        $document = $this->prophesize(ArticleDocument::class);
        $liveNode = $this->prophesize(NodeInterface::class);
        $draftNode = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($liveNode->reveal());

        $this->documentInspector->getNode($document->reveal())->willReturn($draftNode->reveal());

        $children = [
            $this->createNodeMock('123-123-123'),
            $this->createNodeMock('456-456-456'),
        ];

        $liveNode->getNodes()->willReturn($children);
        $draftNode->getNodes()->willReturn($children);

        $this->articleSubscriber->synchronizeChildren($event->reveal());
    }

    public function testSynchronizeChildrenDifferenceRemove()
    {
        $document = $this->prophesize(ArticleDocument::class);
        $liveNode = $this->prophesize(NodeInterface::class);
        $draftNode = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($liveNode->reveal());

        $this->documentInspector->getNode($document->reveal())->willReturn($draftNode->reveal());

        $children = [
            $this->createNodeMock('123-123-123'),
            $this->createNodeMock('456-456-456', true),
        ];

        $liveNode->getNodes()->willReturn($children);
        $draftNode->getNodes()->willReturn([$children[0]]);

        $this->articleSubscriber->synchronizeChildren($event->reveal());
    }

    public function testSynchronizeChildrenDifferenceAdd()
    {
        $document = $this->prophesize(ArticleDocument::class);
        $liveNode = $this->prophesize(NodeInterface::class);
        $draftNode = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($liveNode->reveal());

        $this->documentInspector->getNode($document->reveal())->willReturn($draftNode->reveal());

        $children = [
            $this->createNodeMock('123-123-123'),
            $this->createNodeMock('456-456-456'),
        ];

        $liveNode->getNodes()->willReturn([$children[0]]);
        $draftNode->getNodes()->willReturn($children);

        // nothing should happen because publish new children will be done somewhere else

        $this->articleSubscriber->synchronizeChildren($event->reveal());
    }

    public function testSynchronizeChildrenDifferenceAddAndRemove()
    {
        $document = $this->prophesize(ArticleDocument::class);
        $liveNode = $this->prophesize(NodeInterface::class);
        $draftNode = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($liveNode->reveal());

        $this->documentInspector->getNode($document->reveal())->willReturn($draftNode->reveal());

        $children = [
            $this->createNodeMock('123-123-123', true),
            $this->createNodeMock('456-456-456'),
        ];

        $liveNode->getNodes()->willReturn([$children[0]]);
        $draftNode->getNodes()->willReturn([$children[1]]);

        $this->articleSubscriber->synchronizeChildren($event->reveal());
    }

    private function createNodeMock($uuid, $removeCall = false)
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getIdentifier()->willReturn($uuid);

        if ($removeCall) {
            $node->remove()->shouldBeCalled();
        } else {
            $node->remove()->shouldNotBeCalled();
        }

        return $node->reveal();
    }

    public function testRemoveDraftChildren()
    {
        $children = [
            $this->prophesize(ArticlePageDocument::class)->reveal(),
            $this->prophesize(ArticlePageDocument::class)->reveal(),
            $this->prophesize(ArticlePageDocument::class)->reveal(),
        ];

        $this->document->getChildren()->willReturn($children);

        foreach ($children as $child) {
            $this->documentInspector->getLocalizationState($child)->willReturn(LocalizationState::LOCALIZED);
            $this->documentManager->removeDraft($child, $this->locale)->shouldBeCalled();
        }

        $this->articleSubscriber->removeDraftChildren($this->prophesizeEvent(RemoveDraftEvent::class, $this->locale));
    }

    public function testRemoveDraftChildrenNotExists()
    {
        $child = $this->prophesize(ArticlePageDocument::class)->reveal();

        $this->document->getChildren()->willReturn([$child]);

        $this->documentInspector->getLocalizationState($child)->willReturn(LocalizationState::LOCALIZED);
        $this->documentManager->removeDraft($child, $this->locale)->shouldBeCalled()
            ->willThrow(new PathNotFoundException());

        $node = $this->prophesize(NodeInterface::class);
        $node->remove()->shouldBeCalled();
        $this->documentInspector->getNode($child)->willReturn($node->reveal());

        $this->articleSubscriber->removeDraftChildren($this->prophesizeEvent(RemoveDraftEvent::class, $this->locale));
    }

    public function testSetChildrenStructureType()
    {
        $children = [
            $this->prophesize(ArticlePageDocument::class),
            $this->prophesize(ArticlePageDocument::class),
            $this->prophesize(ArticlePageDocument::class),
        ];

        $this->document->getStructureType()->willReturn('test');
        $this->document->getChildren()->willReturn(
            array_map(
                function ($child) {
                    return $child->reveal();
                },
                $children
            )
        );

        foreach ($children as $child) {
            $this->documentInspector->getLocalizationState($child)->willReturn(LocalizationState::LOCALIZED);
            $this->documentManager->persist($child, $this->locale, Argument::any())->shouldBeCalled();
            $child->getStructureType()->willReturn('my-test');
            $child->setStructureType('test')->shouldBeCalled();
        }

        $this->articleSubscriber->setChildrenStructureType(
            $this->prophesizeEvent(PersistEvent::class, $this->locale, [])
        );
    }

    public function testHydratePagData()
    {
        $node = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(HydrateEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn($this->locale);

        $propertyName = 'i18n:' . $this->locale . '-' . ArticleSubscriber::PAGES_PROPERTY;
        $this->propertyEncoder->localizedSystemName(ArticleSubscriber::PAGES_PROPERTY, $this->locale)
            ->willReturn($propertyName);

        $node->getPropertyValueWithDefault($propertyName, json_encode([]))
            ->willReturn(json_encode([['title' => 'Test title']]));

        $this->document->setPages([['title' => 'Test title']])->shouldBeCalled();

        $this->articleSubscriber->hydratePageData($event->reveal());
    }

    public function testPersistPageData()
    {
        $node = $this->prophesize(NodeInterface::class);

        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn($this->locale);

        $pages = [
            [
                'uuid' => '123-123-123',
                'title' => 'Test article: page 1',
                'routePath' => '/test-article',
                'pageNumber' => 1,
            ],
            [
                'uuid' => '321-321-321',
                'title' => 'Test article: page 2',
                'routePath' => '/test-article/page-2',
                'pageNumber' => 2,
            ],
        ];

        $this->document->getUuid()->willReturn($pages[0]['uuid']);
        $this->document->getPageTitle()->willReturn($pages[0]['title']);
        $this->document->getRoutePath()->willReturn($pages[0]['routePath']);
        $this->document->getPageNumber()->willReturn($pages[0]['pageNumber']);

        $child = $this->prophesize(ArticlePageDocument::class);
        $child->getUuid()->willReturn($pages[1]['uuid']);
        $child->getPageTitle()->willReturn($pages[1]['title']);
        $child->getRoutePath()->willReturn($pages[1]['routePath']);
        $child->getPageNumber()->willReturn($pages[1]['pageNumber']);
        $this->document->getChildren()->willReturn(new \ArrayIterator([$child->reveal()]));

        $this->documentInspector->getLocalizationState($child->reveal())->willReturn(LocalizationState::LOCALIZED);

        $propertyName = 'i18n:' . $this->locale . '-' . ArticleSubscriber::PAGES_PROPERTY;
        $this->propertyEncoder->localizedSystemName(ArticleSubscriber::PAGES_PROPERTY, $this->locale)
            ->willReturn($propertyName);

        $this->document->setPages($pages)->shouldBeCalled();
        $node->setProperty($propertyName, json_encode($pages))->shouldBeCalled();

        $this->articleSubscriber->persistPageData($event->reveal());
    }
}
