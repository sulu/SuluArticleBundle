<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Command;

use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadTrait;

class UnpublishArticle extends Command
{
    use PayloadTrait;

    public function id(): string
    {
        return $this->payload()['id'];
    }

    public function locale(): string
    {
        return $this->payload()['locale'];
    }

    public function userId(): int
    {
        return $this->payload()['userId'];
    }
}
