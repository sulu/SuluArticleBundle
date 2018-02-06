<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model;

interface ArticleRepository
{
    public function save(Article $user): void;
    public function get(string $id): ?Article;
}
