<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Subscriber;

use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handle specialized article events.
 */
class ArticlePageSubscriber implements EventSubscriberInterface
{
    const PAGE_TITLE_TAG_NAME = 'sulu_article.page_title';
    const PAGE_TITLE_PROPERTY_NAME = 'pageTitle';

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     * @param DocumentManagerInterface $documentManager
     * @param DocumentInspector $documentInspector
     */
    public function __construct(
        StructureMetadataFactoryInterface $structureMetadataFactory,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => [
                ['setTitleOnPersist', 2048],
                ['setStructureTypeToParent', -2048],
                ['setWorkflowStageOnArticle', -2048],
            ],
        ];
    }

    /**
     * Set page-title from structure to document.
     *
     * @param PersistEvent $event
     */
    public function setTitleOnPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $pageTitle = uniqid('page-', true);
        $pageTitleProperty = $this->getPageTitleProperty($document);

        if (!$pageTitleProperty) {
            $document->setTitle($pageTitle);

            return;
        }

        if (array_key_exists($pageTitleProperty->getName(), $document->getStructure()->getStagedData())) {
            $pageTitle = $document->getStructure()->getStagedData()[$pageTitleProperty->getName()];
        } elseif ($document->getStructure()->hasProperty($pageTitleProperty->getName())) {
            $pageTitle = $document->getStructure()->getProperty($pageTitleProperty->getName())->getValue();
        }

        $document->setTitle($pageTitle);
    }

    /**
     * Set workflow-stage to test for article.
     *
     * @param PersistEvent $event
     */
    public function setWorkflowStageOnArticle(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument
            || $this->documentInspector->getLocalizationState($document->getParent()) === LocalizationState::GHOST
        ) {
            return;
        }

        $document->getParent()->setWorkflowStage(WorkflowStage::TEST);
        $this->documentManager->persist($document->getParent(), $event->getLocale(), $event->getOptions());
    }

    /**
     * Find page-title property.
     *
     * @param ArticlePageDocument $document
     *
     * @return PropertyMetadata
     */
    private function getPageTitleProperty(ArticlePageDocument $document)
    {
        $metadata = $this->structureMetadataFactory->getStructureMetadata(
            'article_page',
            $document->getStructureType()
        );

        if ($metadata->hasPropertyWithTagName(self::PAGE_TITLE_TAG_NAME)) {
            return $metadata->getPropertyByTagName(self::PAGE_TITLE_TAG_NAME);
        }

        if ($metadata->hasProperty(self::PAGE_TITLE_PROPERTY_NAME)) {
            return $metadata->getProperty(self::PAGE_TITLE_PROPERTY_NAME);
        }

        return null;
    }

    /**
     * Set structure-type to parent document.
     *
     * @param PersistEvent $event
     */
    public function setStructureTypeToParent(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument
            || $this->documentInspector->getLocalizationState($document->getParent()) === LocalizationState::GHOST
            || $document->getStructureType() === $document->getParent()->getStructureType()
        ) {
            return;
        }

        $document->getParent()->setStructureType($document->getStructureType());
        $this->documentManager->persist($document->getParent(), $event->getLocale());
    }
}
