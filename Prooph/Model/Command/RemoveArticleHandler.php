<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Command;

use Sulu\Bundle\ArticleBundle\Prooph\Model\Article;
use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepositoryInterface;

class RemoveArticleHandler
{
    /**
     * @var ArticleRepositoryInterface
     */
    private $repository;

    public function __construct(ArticleRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(RemoveArticleCommand $command): void
    {
        $article = $this->repository->get($command->id());

        $article = $article->remove($command->userId());
        $this->repository->save($article);
    }
}
