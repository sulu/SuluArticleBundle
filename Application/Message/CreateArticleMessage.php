<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Application\Message;

use Webmozart\Assert\Assert;

/**
 * @experimental
 */
class CreateArticleMessage
{
    /**
     * @var mixed[]
     */
    private $data;

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        Assert::string($data['locale'] ?? null, 'Expected a "locale" string given.');
        Assert::nullOrString($data['uuid'] ?? null, 'Expected "uuid" to be a string.');

        $this->data = $data;
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }
}
