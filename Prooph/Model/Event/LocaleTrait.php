<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

trait LocaleTrait
{
    public function locale(): string
    {
        return $this->payload['locale'];
    }
}
