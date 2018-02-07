<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

use Prooph\EventSourcing\AggregateChanged;

class ModifyTranslationStructure extends AggregateChanged
{
    public function locale(): string
    {
        return $this->payload['locale'];
    }

    public function structureData(): array
    {
        return $this->payload['structureData'];
    }

    public function createdBy(): int
    {
        return $this->payload['createdBy'];
    }

    /**
     * FIXME remove this when document-manager is removed
     */
    public function requestData(): array
    {
        return $this->payload['requestData'];
    }
}
