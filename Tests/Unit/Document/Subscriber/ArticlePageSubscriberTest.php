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
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\ArticlePageSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ArticlePageSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StructureMetadataFactoryInterface
     */
    private $factory;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

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
     * @var ArticleDocument
     */
    private $parentDocument;

    /**
     * @var string
     */
    private $locale = 'de';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->metadata = $this->prophesize(StructureMetadata::class);
        $this->document = $this->prophesize(ArticlePageDocument::class);
        $this->parentDocument = $this->prophesize(ArticleDocument::class);

        $this->document->getStructureType()->willReturn('default');
        $this->factory->getStructureMetadata('article_page', 'default')->willReturn($this->metadata->reveal());

        $this->subscriber = new ArticlePageSubscriber(
            $this->factory->reveal(),
            $this->documentManager->reveal(),
            $this->documentInspector->reveal()
        );
    }

    private function createEvent($className, $node = null, $accessor = null, $options = null)
    {
        $event = $this->prophesize($className);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getLocale()->willReturn($this->locale);

        if (null !== $node) {
            $event->getNode()->willReturn($node);
        }

        if (null !== $accessor) {
            $event->getAccessor()->willReturn($accessor);
        }

        if (null !== $options) {
            $event->getOptions()->willReturn($options);
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
        $structure->hasProperty('pageTitle')->willReturn(false);
        $structure->getStagedData()->willReturn(['pageTitle' => 'Test title']);
        $this->document->getStructure()->willReturn($structure->reveal());

        $this->document->setTitle('Test title')->shouldBeCalled();

        $this->subscriber->setTitleOnPersist($event);
    }

    public function testSetTitleOnPersistHasProperty()
    {
        $event = $this->createEvent(PersistEvent::class);

        $property = $this->prophesize(PropertyMetadata::class);
        $property->getName()->willReturn('pageTitle');

        $this->metadata->hasPropertyWithTagName(ArticlePageSubscriber::PAGE_TITLE_TAG_NAME)->willReturn(true);
        $this->metadata->hasProperty(ArticlePageSubscriber::PAGE_TITLE_PROPERTY_NAME)->willReturn(false);
        $this->metadata->getPropertyByTagName(ArticlePageSubscriber::PAGE_TITLE_TAG_NAME)->willReturn(
            $property->reveal()
        );

        $propertyValue = $this->prophesize(PropertyValue::class);
        $propertyValue->getValue()->willReturn('Test title');

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getStagedData()->willReturn([]);
        $structure->hasProperty('pageTitle')->willReturn(true);
        $structure->getProperty('pageTitle')->willReturn($propertyValue->reveal());
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
        $structure->hasProperty('pageTitle')->willReturn(false);
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

        $structure = $this->prophesize(StructureInterface::class);
        $structure->hasProperty('pageTitle')->willReturn(false);
        $this->document->getStructure()->willReturn($structure->reveal());

        $this->document->setTitle(Argument::type('string'))->shouldBeCalled();

        $this->subscriber->setTitleOnPersist($event);
    }

    public function testSetWorkflowStageOnArticle()
    {
        $this->document->getParent()->willReturn($this->parentDocument->reveal());

        $this->documentInspector->getLocalizationState($this->parentDocument->reveal())
            ->willReturn(LocalizationState::LOCALIZED);

        $this->parentDocument->setWorkflowStage(WorkflowStage::TEST)->shouldBeCalled();
        $this->documentManager->persist($this->parentDocument->reveal(), $this->locale, Argument::any())
            ->shouldBeCalled();

        $this->subscriber->setWorkflowStageOnArticle($this->createEvent(PersistEvent::class, null, null, []));
    }

    public function testSetWorkflowStageOnArticleGhost()
    {
        $this->document->getParent()->willReturn($this->parentDocument->reveal());

        $this->documentInspector->getLocalizationState($this->parentDocument->reveal())
            ->willReturn(LocalizationState::GHOST);

        $this->parentDocument->setWorkflowStage(WorkflowStage::TEST)->shouldNotBeCalled();
        $this->documentManager->persist($this->parentDocument->reveal(), $this->locale, Argument::any())
            ->shouldNotBeCalled();

        $this->subscriber->setWorkflowStageOnArticle($this->createEvent(PersistEvent::class));
    }
}
