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
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\ArticlePageSubscriber;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ArticlePageSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StructureMetadataFactoryInterface
     */
    private $factory;

    /**
     * @var ArticlePageSubscriber
     */
    private $subscriber;

    /**
     * @var StructureMetadata
     */
    private $metadata;

    /**
     * @var ArticlePageDocument
     */
    private $document;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->metadata = $this->prophesize(StructureMetadata::class);
        $this->document = $this->prophesize(ArticlePageDocument::class);

        $this->document->getStructureType()->willReturn('default');
        $this->factory->getStructureMetadata('article_page', 'default')->willReturn($this->metadata->reveal());

        $this->subscriber = new ArticlePageSubscriber($this->factory->reveal());
    }

    private function createEvent($className, $node = null, $accessor = null)
    {
        $event = $this->prophesize($className);
        $event->getDocument()->willReturn($this->document->reveal());

        if ($node) {
            $event->getNode()->willReturn($node);
        }

        if ($accessor) {
            $event->getAccessor()->willReturn($accessor);
        }

        return $event->reveal();
    }

    public function testSetTitleOnPersist()
    {
        $event = $this->createEvent(PersistEvent::class);

        $property = $this->prophesize(PropertyMetadata::class);
        $property->getName()->willReturn('pageTitle');

        $this->metadata->hasPropertyWithTagName(ArticlePageSubscriber::PAGE_TITLE_TAG_NAME)->willReturn(true);
        $this->metadata->hasProperty(ArticlePageSubscriber::PAGE_TITLE_PROPERTY_NAME)->willReturn(false);
        $this->metadata->getPropertyByTagName(ArticlePageSubscriber::PAGE_TITLE_TAG_NAME)->willReturn(
            $property->reveal()
        );

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getStagedData()->willReturn(['pageTitle' => 'Test title']);
        $this->document->getStructure()->willReturn($structure->reveal());

        $this->document->setTitle('Test title')->shouldBeCalled();

        $this->subscriber->setTitleOnPersist($event);
    }

    public function testSetTitleOnPersistWithoutTag()
    {
        $event = $this->createEvent(PersistEvent::class);

        $property = $this->prophesize(PropertyMetadata::class);
        $property->getName()->willReturn('pageTitle');

        $this->metadata->hasPropertyWithTagName(ArticlePageSubscriber::PAGE_TITLE_TAG_NAME)->willReturn(false);
        $this->metadata->hasProperty(ArticlePageSubscriber::PAGE_TITLE_PROPERTY_NAME)->willReturn(true);
        $this->metadata->getProperty(ArticlePageSubscriber::PAGE_TITLE_PROPERTY_NAME)->willReturn(
            $property->reveal()
        );

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getStagedData()->willReturn(['pageTitle' => 'Test title']);
        $this->document->getStructure()->willReturn($structure->reveal());

        $this->document->setTitle('Test title')->shouldBeCalled();

        $this->subscriber->setTitleOnPersist($event);
    }

    public function testSetTitleOnPersistWithoutTagAndProperty()
    {
        $event = $this->createEvent(PersistEvent::class);

        $property = $this->prophesize(PropertyMetadata::class);
        $property->getName()->willReturn('pageTitle');

        $this->metadata->hasPropertyWithTagName(ArticlePageSubscriber::PAGE_TITLE_TAG_NAME)->willReturn(false);
        $this->metadata->hasProperty(ArticlePageSubscriber::PAGE_TITLE_PROPERTY_NAME)->willReturn(false);

        $this->document->setTitle(Argument::type('string'))->shouldBeCalled();

        $this->subscriber->setTitleOnPersist($event);
    }

    public function testSetPageNumberOnPersist()
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getIndex()->willReturn(1);

        $accessor = $this->prophesize(DocumentAccessor::class);
        $accessor->set('pageNumber', 2)->shouldBeCalled();

        $event = $this->createEvent(PersistEvent::class, $node->reveal(), $accessor->reveal());

        $this->subscriber->setPageNumberOnPersist($event);
    }

    public function testSetPageNumberOnHydrate()
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getIndex()->willReturn(1);

        $accessor = $this->prophesize(DocumentAccessor::class);
        $accessor->set('pageNumber', 2)->shouldBeCalled();

        $event = $this->createEvent(HydrateEvent::class, $node->reveal(), $accessor->reveal());

        $this->subscriber->setPageNumberOnHydrate($event);
    }
}
