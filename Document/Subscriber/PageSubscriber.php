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

use Sulu\Bundle\ArticleBundle\Document\Behavior\PageBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events to set and update page-numbers.
 */
class PageSubscriber implements EventSubscriberInterface
{
    const FIELD = 'pageNumber';

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @param DocumentInspector $documentInspector
     * @param PropertyEncoder $propertyEncoder
     */
    public function __construct(DocumentInspector $documentInspector, PropertyEncoder $propertyEncoder)
    {
        $this->documentInspector = $documentInspector;
        $this->propertyEncoder = $propertyEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => [['handlePersist', -1024]],
            Events::REMOVE => [['handleRemove', 5]],
            Events::METADATA_LOAD => 'handleMetadataLoad',
        ];
    }

    /**
     * Add page-number to metadata.
     *
     * @param MetadataLoadEvent $event
     */
    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(PageBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping(
            'pageNumber',
            [
                'encoding' => 'system',
                'property' => self::FIELD,
            ]
        );
    }

    /**
     * Set the page-number to new pages.
     *
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof PageBehavior || $document->getPageNumber()) {
            return;
        }

        $parentDocument = $document->getParent();

        $page = 1;
        foreach ($parentDocument->getChildren() as $child) {
            if (!$child instanceof PageBehavior) {
                continue;
            }

            ++$page;
        }

        $childNode = $this->documentInspector->getNode($document);
        $childNode->setProperty($this->propertyEncoder->systemName(static::FIELD), $page);
    }

    /**
     * Adjust the page-numbers of siblings when removing a page.
     *
     * @param RemoveEvent $event
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof PageBehavior) {
            return;
        }

        $parentDocument = $document->getParent();

        $page = 1;
        foreach ($parentDocument->getChildren() as $child) {
            if (!$child instanceof PageBehavior || $child->getUuid() === $document->getUuid()) {
                continue;
            }

            $childNode = $this->documentInspector->getNode($child);
            $childNode->setProperty($this->propertyEncoder->systemName(static::FIELD), $page++);
        }
    }
}
