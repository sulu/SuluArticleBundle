<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Projection;

use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\CreateTranslation;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ModifyTranslationStructure;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\PublishTranslation;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\RemoveArticle;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\UnpublishTranslation;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class ArticleIndexProjector
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var IndexerInterface
     */
    private $liveIndexer;

    public function __construct(
        DocumentManagerInterface $documentManager,
        IndexerInterface $indexer,
        IndexerInterface $liveIndexer
    ) {
        $this->documentManager = $documentManager;
        $this->indexer = $indexer;
        $this->liveIndexer = $liveIndexer;
    }

    public function onCreateTranslation(CreateTranslation $event): void
    {
        $document = $this->documentManager->find($event->aggregateId(), $event->locale());
        $this->documentManager->refresh($document, $event->locale());

        $this->indexer->index($document);
        $this->indexer->flush();
    }

    public function onModifyTranslationStructure(ModifyTranslationStructure $event): void
    {
        $document = $this->documentManager->find($event->aggregateId(), $event->locale());
        $this->documentManager->refresh($document, $event->locale());

        $this->indexer->index($document);
        $this->indexer->flush();
    }

    public function onPublishTranslation(PublishTranslation $event): void
    {
        $document = $this->documentManager->find($event->aggregateId(), $event->locale());
        $this->documentManager->refresh($document, $event->locale());

        $this->indexer->index($document);
        $this->indexer->flush();

        $this->liveIndexer->index($document);
        $this->liveIndexer->flush();
    }

    public function onUnpublishTranslation(UnpublishTranslation $event): void
    {
        $document = $this->documentManager->find($event->aggregateId(), $event->locale());
        $this->documentManager->refresh($document, $event->locale());

        $this->liveIndexer->remove($event->aggregateId());
        $this->liveIndexer->flush();

        $this->indexer->setUnpublished($document->getUuid(), $event->locale());
        $this->indexer->flush();
    }

    public function onRemoveArticle(RemoveArticle $event): void
    {
        $this->indexer->remove($event->aggregateId());
        $this->indexer->flush();

        $this->liveIndexer->remove($event->aggregateId());
        $this->indexer->flush();
    }
}
