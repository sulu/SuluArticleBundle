<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ArticleBundle\Document\Behavior\PageBehavior;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\PageSubscriber;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

class PageSubscriberTest extends TestCase
{
    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var PageSubscriber
     */
    private $pageSubscriber;

    /**
     * @var PageBehavior
     */
    private $document;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);

        $this->document = $this->prophesize(PageBehavior::class);
        $this->node = $this->prophesize(NodeInterface::class);

        $this->pageSubscriber = new PageSubscriber(
            $this->documentInspector->reveal(), $this->propertyEncoder->reveal(), $this->documentManager->reveal()
        );
    }

    public function testHandlePersist()
    {
        $child1 = $this->prophesize(\stdClass::class);
        $child2 = $this->prophesize(PageBehavior::class);
        $child2->getUuid()->willReturn('1-1-1');
        $child3 = $this->prophesize(PageBehavior::class);
        $child3->getUuid()->willReturn('1-2-2');

        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getNode()->willReturn($this->node->reveal());

        $this->node->hasProperty('sulu:pageNumber')->willReturn(false);

        $parentDocument = $this->prophesize(ChildrenBehavior::class);
        $parentDocument->getChildren()->willReturn(
            [
                $child1->reveal(),
                $child2->reveal(),
                $child3->reveal(),
            ]
        );
        $this->document->getParent()->willReturn($parentDocument->reveal());
        $this->document->getUuid()->willReturn('1-2-3');
        $this->document->getPageNumber()->willReturn(null);
        $this->document->setPageNumber(3)->shouldBeCalled();

        $this->propertyEncoder->systemName(PageSubscriber::FIELD)->willReturn('sulu:' . PageSubscriber::FIELD);

        $this->node->setProperty('sulu:' . PageSubscriber::FIELD, 3)->shouldBeCalled();

        $this->pageSubscriber->handlePersist($event->reveal());
    }

    public function testHandleRemove()
    {
        $child1 = $this->prophesize(\stdClass::class);
        $child2 = $this->prophesize(PageBehavior::class);
        $child2->getUuid()->willReturn('1-1-1');
        $child3 = $this->prophesize(PageBehavior::class);
        $child3->getUuid()->willReturn('1-2-2');

        $childNode2 = $this->prophesize(NodeInterface::class);
        $this->documentInspector->getNode($child2->reveal())->willReturn($childNode2->reveal());
        $childNode3 = $this->prophesize(NodeInterface::class);
        $this->documentInspector->getNode($child3->reveal())->willReturn($childNode3->reveal());

        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());

        $parentDocument = $this->prophesize(ChildrenBehavior::class);
        $parentDocument->getChildren()->willReturn(
            [
                $child1->reveal(),
                $child2->reveal(),
                $this->document->reveal(),
                $child3->reveal(),
            ]
        );
        $this->document->getParent()->willReturn($parentDocument->reveal());
        $this->document->getUuid()->willReturn('1-2-3');

        $this->propertyEncoder->systemName(PageSubscriber::FIELD)->willReturn('sulu:' . PageSubscriber::FIELD);

        $childNode2->setProperty('sulu:' . PageSubscriber::FIELD, 2)->shouldBeCalled();
        $childNode3->setProperty('sulu:' . PageSubscriber::FIELD, 3)->shouldBeCalled();

        $this->pageSubscriber->handleRemove($event->reveal());
    }

    public function testHandlePublishPageNumber()
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getNode()->willReturn($this->node->reveal());

        $this->propertyEncoder->systemName(PageSubscriber::FIELD)->willReturn('sulu:' . PageSubscriber::FIELD);

        $this->document->getPageNumber()->willReturn(1);
        $this->node->setProperty('sulu:' . PageSubscriber::FIELD, 1)->shouldBeCalled();

        $this->pageSubscriber->handlePublishPageNumber($event->reveal());
    }

    public function testHandleRestore()
    {
        $event = $this->prophesize(RestoreEvent::class);

        $parentDocument = $this->prophesize(ChildrenBehavior::class);
        $parentDocument->getChildren()->willReturn([$this->document->reveal()]);

        $event->getDocument()->willReturn($parentDocument->reveal());
        $this->documentInspector->getNode($this->document->reveal())->willReturn($this->node->reveal());

        $this->propertyEncoder->systemName(PageSubscriber::FIELD)->willReturn('sulu:' . PageSubscriber::FIELD);

        $this->document->getPageNumber()->willReturn(2);
        $this->node->setProperty('sulu:' . PageSubscriber::FIELD, 2)->shouldBeCalled();

        $this->pageSubscriber->handleRestore($event->reveal());
    }

    public function testHandleReorder()
    {
        $parentDocument = $this->prophesize(ChildrenBehavior::class);
        $this->document->getParent()->willReturn($parentDocument->reveal());

        $nodes = [
            $this->prophesize(NodeInterface::class),
            $this->prophesize(NodeInterface::class),
            $this->prophesize(NodeInterface::class),
        ];

        $nodes[0]->getIdentifier()->willReturn('1-1-1');
        $nodes[1]->getIdentifier()->willReturn('1-2-2');
        $nodes[2]->getIdentifier()->willReturn('1-2-3');

        $parentNode = $this->prophesize(NodeInterface::class);
        $parentNode->getNodes()->willReturn(
            array_map(
                function($item) {
                    return $item->reveal();
                },
                $nodes
            )
        );

        $this->documentInspector->getNode($parentDocument->reveal())->willReturn($parentNode->reveal());

        $documents = [
            $this->prophesize(PageBehavior::class),
            $this->prophesize(PageBehavior::class),
            $this->prophesize(PageBehavior::class),
        ];

        $this->documentManager->find('1-1-1', 'de')->willReturn($documents[0]->reveal());
        $this->documentManager->find('1-2-2', 'de')->willReturn($documents[1]->reveal());
        $this->documentManager->find('1-2-3', 'de')->willReturn($documents[2]->reveal());

        $event = $this->prophesize(ReorderEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->systemName(PageSubscriber::FIELD)->willReturn('sulu:' . PageSubscriber::FIELD);

        $documents[0]->setPageNumber(2)->shouldBeCalled();
        $nodes[0]->setProperty('sulu:' . PageSubscriber::FIELD, 2)->shouldBeCalled();
        $documents[1]->setPageNumber(3)->shouldBeCalled();
        $nodes[1]->setProperty('sulu:' . PageSubscriber::FIELD, 3)->shouldBeCalled();
        $documents[2]->setPageNumber(4)->shouldBeCalled();
        $nodes[2]->setProperty('sulu:' . PageSubscriber::FIELD, 4)->shouldBeCalled();

        $this->pageSubscriber->handleReorder($event->reveal());
    }
}
