<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

trait StructureTrait
{
    public function structureType(): string
    {
        return $this->payload['structureType'];
    }

    public function structureData(): array
    {
        return $this->payload['structureData'];
    }
}
