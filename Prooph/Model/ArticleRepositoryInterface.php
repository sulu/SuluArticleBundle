<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model;

interface ArticleRepositoryInterface
{
    public function create(string $id, int $userId): Article;
    public function save(Article $user): void;
    public function get(string $id): ?Article;
}
