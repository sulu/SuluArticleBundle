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

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Indexes article on persist and removes it from index on delete.
 */
class ArticleSubscriber implements EventSubscriberInterface
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @param IndexerInterface $indexer
     */
    public function __construct(IndexerInterface $indexer)
    {
        $this->indexer = $indexer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => [['handlePersist', -500]],
            Events::REMOVE => [['handleRemove', -500]],
        ];
    }

    /**
     * Indexes article-document.
     *
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->indexer->index($document);
        $this->indexer->flush();
    }

    /**
     * Removes article-document.
     *
     * @param RemoveEvent $event
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->indexer->remove($document);
        $this->indexer->flush();
    }
}
