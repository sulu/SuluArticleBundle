<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model;

use Prooph\EventSourcing\AggregateChanged;

interface EventResolverInterface
{
    public function resolve(AggregateChanged $event);
}
