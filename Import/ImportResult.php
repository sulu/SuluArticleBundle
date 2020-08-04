<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Import;

class ImportResult
{
    /**
     * @var int
     */
    private $count;

    /**
     * @var int
     */
    private $fails;

    /**
     * @var int
     */
    private $successes;

    /**
     * @var array
     */
    private $failed;

    /**
     * @var \Exception[]
     */
    private $exceptionStore;

    public function __construct(int $count, int $fails, int $successes, array $failed, array $exceptionStore)
    {
        $this->count = $count;
        $this->fails = $fails;
        $this->successes = $successes;
        $this->failed = $failed;
        $this->exceptionStore = $exceptionStore;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getFails(): int
    {
        return $this->fails;
    }

    public function getSuccesses(): int
    {
        return $this->successes;
    }

    public function getFailed(): array
    {
        return $this->failed;
    }

    public function getExceptionStore(): array
    {
        return $this->exceptionStore;
    }
}
