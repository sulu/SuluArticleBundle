<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

use Prooph\EventSourcing\AggregateChanged;

class CreateArticle extends AggregateChanged
{
    public function createdBy(): int
    {
        return $this->payload['createdBy'];
    }
}
