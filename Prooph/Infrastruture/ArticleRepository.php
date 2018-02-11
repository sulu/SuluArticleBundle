<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Infrastruture;

use Prooph\EventSourcing\Aggregate\AggregateRepository;
use Prooph\EventSourcing\Aggregate\AggregateType;
use Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator;
use Prooph\EventStore\EventStore;
use Prooph\SnapshotStore\SnapshotStore;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Article;
use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepositoryInterface;

class ArticleRepository extends AggregateRepository implements ArticleRepositoryInterface
{
    /**
     * @var string
     */
    private $className;

    public function __construct(string $className, EventStore $eventStore, SnapshotStore $snapshotStore)
    {
        parent::__construct(
            $eventStore,
            AggregateType::fromAggregateRootClass($className),
            new AggregateTranslator(),
            $snapshotStore,
            null,
            true
        );

        $this->className = $className;
    }

    public function create(string $id, int $userId): Article
    {
        return $this->className::create($id, $userId);
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
