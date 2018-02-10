<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Resolver;

interface EventResolverInterface
{
    public function getResolvingEvents(): array;
}
