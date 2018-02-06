<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Infrastruture;

use Prooph\EventSourcing\Aggregate\AggregateRepository;
use Prooph\EventSourcing\Aggregate\AggregateType;
use Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator;
use Prooph\EventStore\EventStore;
use Prooph\SnapshotStore\SnapshotStore;
use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepository as BaseArticleRepository;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Article;

class ArticleRepository extends AggregateRepository implements BaseArticleRepository
{
    public function __construct(EventStore $eventStore, SnapshotStore $snapshotStore)
    {
        parent::__construct(
            $eventStore,
            AggregateType::fromAggregateRootClass(Article::class),
            new AggregateTranslator(),
            $snapshotStore,
            null,
            true
        );
    }

    public function save(Article $article): void
    {
        $this->saveAggregateRoot($article);
    }

    public function get(string $id): ?Article
    {
        return $this->getAggregateRoot($id);
    }
}
