<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Command;

use Sulu\Bundle\ArticleBundle\Prooph\Model\Article;
use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepository;

class UnpublishArticleHandler
{
    /**
     * @var ArticleRepository
     */
    private $repository;

    public function __construct(ArticleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(UnpublishArticle $command): void
    {
        $article = $this->repository->get($command->id());

        $article = $article->unpublish($command->locale(), $command->userId());
        $this->repository->save($article);
    }
}
