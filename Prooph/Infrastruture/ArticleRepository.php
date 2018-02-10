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
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\CreateArticle;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Resolver\EventResolverInterface;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Resolver\EventResolverPool;

class ArticleRepository extends AggregateRepository implements ArticleRepositoryInterface
{
    /**
     * @var EventResolverInterface[]
     */
    private $eventResolverPool;

    public function __construct(
        EventStore $eventStore,
        SnapshotStore $snapshotStore,
        EventResolverPool $eventResolverPool
    ) {
        parent::__construct(
            $eventStore,
            AggregateType::fromAggregateRootClass(Article::class),
            new AggregateTranslator(),
            $snapshotStore,
            null,
            true
        );

        Article::$eventResolver = $eventResolverPool;
    }

    public function create(string $id, int $userId): Article
    {
        return Article::create($id, $userId);
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
