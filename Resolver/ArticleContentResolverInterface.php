<?php

namespace Sulu\Bundle\ArticleBundle\Resolver;

use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;

interface ArticleContentResolverInterface
{
    public function resolve(ArticleInterface $article, int $pageNumber = 1);
}
