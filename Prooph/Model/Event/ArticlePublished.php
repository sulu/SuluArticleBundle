<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

use Prooph\EventSourcing\AggregateChanged;

class ArticlePublished extends AggregateChanged
{
    use LocaleTrait, ChangedTrait, PublishedTrait;
}
