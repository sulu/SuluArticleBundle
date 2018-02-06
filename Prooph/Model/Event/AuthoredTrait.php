<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

trait AuthoredTrait
{
    public function author(): int
    {
        return $this->payload['author'];
    }

    public function authored(): \DateTime
    {
        return new \DateTime($this->payload['authored']);
    }
}
