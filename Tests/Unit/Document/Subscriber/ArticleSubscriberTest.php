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

use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\ArticleSubscriber;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
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
     * @var ArticleSubscriber
     */
    private $articleSubscriber;

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

        $this->document = $this->prophesize(ArticleDocument::class);
        $this->document->getUuid()->willReturn($this->uuid);
        $this->document->getLocale()->willReturn($this->locale);
        $this->documentManager->find($this->uuid, $this->locale)->willReturn($this->document->reveal());

        $this->articleSubscriber = new ArticleSubscriber(
            $this->indexer->reveal(),
            $this->liveIndexer->reveal(),
            $this->documentManager->reveal()
        );
    }

    protected function prophesizeEvent($className)
    {
        $event = $this->prophesize($className);
        $event->getDocument()->willReturn($this->document->reveal());

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
}
