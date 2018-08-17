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

use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NameResolver;
use Symfony\Cmf\Api\Slugifier\SlugifierInterface;
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
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * @var NameResolver
     */
    private $resolver;

    /**
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     * @param DocumentManagerInterface $documentManager
     * @param DocumentInspector $documentInspector
     * @param SlugifierInterface $slugifier
     * @param NameResolver $resolver
     */
    public function __construct(
        StructureMetadataFactoryInterface $structureMetadataFactory,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        SlugifierInterface $slugifier,
        NameResolver $resolver
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->slugifier = $slugifier;
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => [
                ['setParentOnHydrate', 1],
            ],
            Events::PERSIST => [
                ['checkOptions', 10000],
                ['setTitleOnPersist', 2000],
                ['setNodeOnPersist', 480],
                ['setPageTitleOnPersist'],
                ['setStructureTypeToParent', -2000],
                ['setWorkflowStageOnArticle', -2000],
            ],
            Events::REMOVE => ['setWorkflowStageOnArticle'],
            Events::METADATA_LOAD => ['handleMetadataLoad'],
        ];
    }

    /**
     * Hydrate parent to avoid proxiing it.
     *
     * @param HydrateEvent $event
     */
    public function setParentOnHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument || $document->getParent()) {
            return;
        }

        $parent = $this->documentManager->find(
            $event->getNode()->getParent()->getIdentifier(),
            $document->getOriginalLocale(),
            $event->getOptions()
        );
        $document->setParent($parent);
    }

    /**
     * Check for missing persist options.
     *
     * @param PersistEvent $event
     */
    public function checkOptions(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $autoRename = $event->getOption('auto_rename');

        if (false !== $autoRename) {
            throw new \InvalidArgumentException('Persist "ArticlePageDocument" only with option "auto_rename" set to "false" allowed');
        }
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

        $document->setTitle($document->getParent()->getTitle());
    }

    /**
     * Set workflow-stage to test for article.
     *
     * @param PersistEvent|RemoveEvent $event
     */
    public function setWorkflowStageOnArticle($event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument
            || LocalizationState::GHOST === $this->documentInspector->getLocalizationState($document->getParent())
        ) {
            return;
        }

        $document->getParent()->setWorkflowStage(WorkflowStage::TEST);
        $this->documentManager->persist(
            $document->getParent(),
            $this->documentInspector->getLocale($document),
            $event instanceof PersistEvent ? $event->getOptions() : []
        );
    }

    /**
     * Set node to event on persist.
     *
     * @param PersistEvent $event
     */
    public function setNodeOnPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument || $event->hasNode()) {
            return;
        }

        $pageTitle = $this->getPageTitle($document);

        // if no page-title exists use a unique-id
        $nodeName = $this->slugifier->slugify($pageTitle ?: uniqid('page-', true));
        $nodeName = $this->resolver->resolveName($event->getParentNode(), $nodeName);
        $node = $event->getParentNode()->addNode($nodeName);

        $event->setNode($node);
    }

    /**
     * Set page-title on persist event.
     *
     * @param PersistEvent $event
     */
    public function setPageTitleOnPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleInterface) {
            return;
        }

        $document->setPageTitle($this->getPageTitle($document));
    }

    /**
     * Returns page-title for node.
     *
     * @param ArticleInterface $document
     *
     * @return string
     */
    private function getPageTitle(ArticleInterface $document)
    {
        $pageTitleProperty = $this->getPageTitleProperty($document);
        if (!$pageTitleProperty) {
            return;
        }

        $stagedData = $document->getStructure()->getStagedData();
        if (array_key_exists($pageTitleProperty->getName(), $stagedData)) {
            return $stagedData[$pageTitleProperty->getName()];
        }

        if (!$document->getStructure()->hasProperty($pageTitleProperty->getName())) {
            return;
        }

        return $document->getStructure()->getProperty($pageTitleProperty->getName())->getValue();
    }

    /**
     * Find page-title property.
     *
     * @param ArticleInterface $document
     *
     * @return PropertyMetadata
     */
    private function getPageTitleProperty(ArticleInterface $document)
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
            || LocalizationState::GHOST === $this->documentInspector->getLocalizationState($document->getParent())
            || $document->getStructureType() === $document->getParent()->getStructureType()
        ) {
            return;
        }

        $document->getParent()->setStructureType($document->getStructureType());
        $this->documentManager->persist($document->getParent(), $event->getLocale());
    }

    /**
     * Extend metadata for article-page.
     *
     * @param MetadataLoadEvent $event
     */
    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        if (ArticlePageDocument::class !== $event->getMetadata()->getClass()
            && !is_subclass_of($event->getMetadata()->getClass(), ArticlePageDocument::class)
        ) {
            return;
        }

        $metadata = $event->getMetadata();
        $metadata->setSyncRemoveLive(false);
        $metadata->addFieldMapping(
            'pageTitle',
            [
                'encoding' => 'system_localized',
                'property' => 'suluPageTitle',
            ]
        );
    }
}
