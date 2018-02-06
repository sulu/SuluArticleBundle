<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

trait CreatedTrait
{
    public function creator(): int
    {
        return $this->payload['creator'];
    }

    public function created(): \DateTime
    {
        return new \DateTime($this->payload['created']);
    }
}
