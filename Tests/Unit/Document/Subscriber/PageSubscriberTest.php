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
use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\Document\Behavior\PageBehavior;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\PageSubscriber;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

class PageSubscriberTest extends \PHPUnit_Framework_TestCase
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
    protected function setUp()
    {
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);

        $this->document = $this->prophesize(PageBehavior::class);
        $this->node = $this->prophesize(NodeInterface::class);

        $this->pageSubscriber = new PageSubscriber(
            $this->documentInspector->reveal(), $this->propertyEncoder->reveal()
        );
    }

    public function testHandlePersist()
    {
        $child1 = $this->prophesize(\stdClass::class);
        $child2 = $this->prophesize(PageBehavior::class);
        $child2->getUuid()->willReturn('1-1-1');
        $child3 = $this->prophesize(PageBehavior::class);
        $child3->getUuid()->willReturn('1-2-2');

        $this->documentInspector->getNode($this->document->reveal())->willReturn($this->node->reveal());

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

    public function testHandlePersistAlreadySet()
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getNode()->willReturn($this->node->reveal());

        $this->node->hasProperty('sulu:pageNumber')->willReturn(true);

        $this->propertyEncoder->systemName(PageSubscriber::FIELD)->willReturn('sulu:' . PageSubscriber::FIELD);

        $this->document->getPageNumber()->willReturn(2);
        $this->document->setPageNumber(Argument::any())->shouldNotBeCalled();

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

        $childNode2->setProperty('sulu:' . PageSubscriber::FIELD, 1)->shouldBeCalled();
        $childNode3->setProperty('sulu:' . PageSubscriber::FIELD, 2)->shouldBeCalled();

        $this->pageSubscriber->handleRemove($event->reveal());
    }
}
