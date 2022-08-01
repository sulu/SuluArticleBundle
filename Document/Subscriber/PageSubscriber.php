<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Subscriber;

use Sulu\Bundle\ArticleBundle\Document\Behavior\PageBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events to set and update page-numbers.
 */
class PageSubscriber implements EventSubscriberInterface
{
    public const FIELD = 'pageNumber';

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

    public function __construct(
        DocumentInspector $documentInspector,
        PropertyEncoder $propertyEncoder,
        DocumentManagerInterface $documentManager
    ) {
        $this->documentInspector = $documentInspector;
        $this->propertyEncoder = $propertyEncoder;
        $this->documentManager = $documentManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => ['handleHydrate'],
            Events::PERSIST => [['handlePersist', -1024]],
            Events::REMOVE => [['handleRemove', 5]],
            Events::PUBLISH => [['handlePublishPageNumber', -1024]],
            Events::REORDER => [['handleReorder', 0]],
            Events::RESTORE => [['handleRestore', -1024]],
        ];
    }

    /**
     * Set the page-number to existing pages.
     */
    public function handleHydrate(HydrateEvent $event): void
    {
        $document = $event->getDocument();
        $node = $event->getNode();
        $propertyName = $this->propertyEncoder->systemName(static::FIELD);
        if (!$document instanceof PageBehavior || !$node->hasProperty($propertyName)) {
            return;
        }

        $node = $event->getNode();
        $document->setPageNumber($node->getPropertyValue($this->propertyEncoder->systemName(static::FIELD)));
    }

    /**
     * Set the page-number to new pages.
     */
    public function handlePersist(PersistEvent $event): void
    {
        $document = $event->getDocument();
        $node = $event->getNode();
        $propertyName = $this->propertyEncoder->systemName(static::FIELD);
        if (!$document instanceof PageBehavior) {
            return;
        }

        $parentDocument = $document->getParent();

        $page = 1;
        foreach ($parentDocument->getChildren() as $child) {
            if (!$child instanceof PageBehavior) {
                continue;
            }

            ++$page;

            if ($child === $document) {
                break;
            }
        }

        $node->setProperty($propertyName, $page);
        $document->setPageNumber($page);
    }

    /**
     * Adjust the page-numbers of siblings when reordering a page.
     */
    public function handleReorder(ReorderEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof PageBehavior) {
            return;
        }

        $propertyName = $this->propertyEncoder->systemName(static::FIELD);
        $parentNode = $this->documentInspector->getNode($document->getParent());

        $page = 1;
        foreach ($parentNode->getNodes() as $childNode) {
            $child = $this->documentManager->find($childNode->getIdentifier(), $event->getLocale());
            if (!$child instanceof PageBehavior) {
                continue;
            }

            $childNode->setProperty($propertyName, ++$page);
            $child->setPageNumber($page);
        }
    }

    /**
     * Copy page-number to live workspace.
     */
    public function handlePublishPageNumber(PublishEvent $event): void
    {
        $document = $event->getDocument();
        $node = $event->getNode();
        $propertyName = $this->propertyEncoder->systemName(static::FIELD);
        if (!$document instanceof PageBehavior) {
            return;
        }

        $node->setProperty($propertyName, $document->getPageNumber());
    }

    /**
     * Adjust the page-numbers of siblings when removing a page.
     */
    public function handleRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof PageBehavior) {
            return;
        }

        $page = 1;
        foreach ($document->getParent()->getChildren() as $child) {
            if (!$child instanceof PageBehavior || $child->getUuid() === $document->getUuid()) {
                continue;
            }

            $childNode = $this->documentInspector->getNode($child);
            $childNode->setProperty($this->propertyEncoder->systemName(static::FIELD), ++$page);
        }
    }

    /**
     * Adjust the page-numbers of siblings when restoring a page.
     */
    public function handleRestore(RestoreEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ChildrenBehavior) {
            return;
        }

        $page = 1;
        foreach ($document->getChildren() as $child) {
            if (!$child instanceof PageBehavior) {
                continue;
            }

            $childNode = $this->documentInspector->getNode($child);
            $childNode->setProperty($this->propertyEncoder->systemName(static::FIELD), ++$page);
        }
    }
}
