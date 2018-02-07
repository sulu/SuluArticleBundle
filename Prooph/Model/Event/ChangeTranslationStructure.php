<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

use Prooph\EventSourcing\AggregateChanged;

class ChangeTranslationStructure extends AggregateChanged
{
    public function locale(): string
    {
        return $this->payload['locale'];
    }

    public function structureType(): string
    {
        return $this->payload['structureType'];
    }

    public function createdBy(): int
    {
        return $this->payload['createdBy'];
    }
}
