<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Article\Domain\Model;

use Ramsey\Uuid\Uuid;
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * @experimental
 */
class Article implements ArticleInterface
{
    use AuditableTrait;

    /**
     * @var string
     */
    private $id;

    public function __construct(
        ?string $id = null
    ) {
        $this->id = $id ?: Uuid::uuid4()->toString();
    }

    public function getId(): string
    {
        return $this->id;
    }
}
