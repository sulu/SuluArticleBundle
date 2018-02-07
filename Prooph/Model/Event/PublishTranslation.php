<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

use Prooph\EventSourcing\AggregateChanged;

class PublishTranslation extends AggregateChanged
{
    public function locale(): string
    {
        return $this->payload['locale'];
    }

    public function createdBy(): int
    {
        return $this->payload['createdBy'];
    }
}
