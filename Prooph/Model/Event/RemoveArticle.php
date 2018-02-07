<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

use Prooph\EventSourcing\AggregateChanged;

class RemoveArticle extends AggregateChanged
{
    public function createdBy(): int
    {
        return $this->payload['createdBy'];
    }
}
