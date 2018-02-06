<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Event;

trait ChangedTrait
{
    public function changer(): int
    {
        return $this->payload['changer'];
    }

    public function changed(): \DateTime
    {
        return new \DateTime($this->payload['changed']);
    }
}
