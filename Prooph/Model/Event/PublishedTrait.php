<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

trait PublishedTrait
{
    public function published(): \DateTime
    {
        return new \DateTime($this->payload['published']);
    }
}
