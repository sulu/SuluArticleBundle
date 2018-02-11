<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Handler;

use Sulu\Bundle\ArticleBundle\Prooph\Model\Article;
use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepositoryInterface;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Command\PublishArticleCommand;

class PublishArticleHandler
{
    /**
     * @var ArticleRepositoryInterface
     */
    private $repository;

    public function __construct(ArticleRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(PublishArticleCommand $command): void
    {
        $article = $this->repository->get($command->id());

        $article = $article->publishTranslation($command->locale(), $command->userId());
        $this->repository->save($article);

        // TODO create route
        // TODO create version
    }
}
