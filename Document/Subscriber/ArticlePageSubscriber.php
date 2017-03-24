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
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Indexes article and generate route on persist and removes it from index and routing on delete.
 */
class ArticlePageSubscriber implements EventSubscriberInterface
{
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
            Events::PERSIST => [['setTitleOnPersist', 2048], ['setPageOnPersist', 0]],
            Events::HYDRATE => [['setPageOnHydrate', 0]],
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

        $metadata = $this->structureMetadataFactory->getStructureMetadata(
            'article_page',
            $document->getStructureType()
        );

        $pageTitleProperty = $metadata->getPropertyByTagName('sulu_article.page_title');
        if (!$pageTitleProperty) {
            $pageTitleProperty = $metadata->getProperty('pageTitle');
        }

        $pageTitle = 'page-' . uniqid('page-1', true);
        if ($pageTitleProperty) {
            $pageTitle = $document->getStructure()->getStagedData()[$pageTitleProperty->getName()];
        }

        $document->setTitle($pageTitle);
    }

    public function setPageOnPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $event->getAccessor()->set('page', $event->getNode()->getIndex() + 1);
    }

    public function setPageOnHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $event->getAccessor()->set('page', $event->getNode()->getIndex() + 1);
    }
}
