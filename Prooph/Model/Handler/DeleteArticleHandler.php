<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Handler;

use Sulu\Bundle\ArticleBundle\Prooph\Model\Article;
use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepositoryInterface;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Command\DeleteArticleCommand;

class DeleteArticleHandler
{
    /**
     * @var ArticleRepositoryInterface
     */
    private $repository;

    public function __construct(ArticleRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(DeleteArticleCommand $command): void
    {
        $article = $this->repository->get($command->id());

        $article = $article->remove($command->userId());
        $this->repository->save($article);
    }
}
