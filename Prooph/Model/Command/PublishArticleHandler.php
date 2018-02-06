<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Command;

use Sulu\Bundle\ArticleBundle\Prooph\Model\Article;
use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepository;

class PublishArticleHandler
{
    /**
     * @var ArticleRepository
     */
    private $repository;

    public function __construct(ArticleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(PublishArticle $command): void
    {
        $article = $this->repository->get($command->id());

        $article = $article->publish($command->locale(), $command->userId());
        $this->repository->save($article);

        // TODO create route
        // TODO create version
    }
}
