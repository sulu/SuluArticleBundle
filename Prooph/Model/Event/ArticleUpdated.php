<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

use Prooph\EventSourcing\AggregateChanged;

class ArticleUpdated extends AggregateChanged
{
    use LocaleTrait, ChangedTrait, StructureTrait;

    /**
     * TODO omit this when document-manager is removed.
     */
    public function data(): array
    {
        return $this->payload['data'];
    }
}
