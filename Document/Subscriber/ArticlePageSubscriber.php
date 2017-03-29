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
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
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
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     */
    public function __construct(StructureMetadataFactoryInterface $structureMetadataFactory)
    {
        $this->structureMetadataFactory = $structureMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => [['setTitleOnPersist', 2048], ['setPageNumberOnPersist', 0]],
            Events::HYDRATE => [['setPageNumberOnHydrate', 0]],
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

        if ($pageTitleProperty) {
            $pageTitle = $document->getStructure()->getStagedData()[$pageTitleProperty->getName()];
        }

        $document->setTitle($pageTitle);
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
     * Set page-number to document on persist.
     *
     * @param PersistEvent $event
     */
    public function setPageNumberOnPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $event->getAccessor()->set('pageNumber', $event->getNode()->getIndex() + 1);
    }

    /**
     * Set page-number to document on persist.
     *
     * @param HydrateEvent $event
     */
    public function setPageNumberOnHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $event->getAccessor()->set('pageNumber', $event->getNode()->getIndex() + 1);
    }
}
