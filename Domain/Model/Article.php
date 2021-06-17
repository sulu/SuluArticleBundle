<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Domain\Model;

use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * @experimental
 */
class Article implements ArticleInterface
{
    use ContentRichEntityTrait;
    use AuditableTrait;

    /**
     * @var string
     */
    protected $id;

    public function __construct(
        ?string $id = null
    ) {
        $this->id = $id ?: Uuid::uuid4()->toString();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return ArticleDimensionContentInterface
     */
    public function createDimensionContent(): DimensionContentInterface
    {
        return new ArticleDimensionContent($this);
    }
}
