<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Exception;

/**
 * Will be thrown when the requested schema is not configured.
 */
class RouteSchemaNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $requested;

    /**
     * @var string[]
     */
    private $available;

    /**
     * @param string[] $available
     */
    public function __construct(string $requested, array $available)
    {
        parent::__construct(
            sprintf('Route-schema for "%s" not configured. Available: ["%s"]', $requested, implode('","', $available))
        );

        $this->requested = $requested;
        $this->available = $available;
    }

    /**
     * Returns requested.
     */
    public function getRequested(): string
    {
        return $this->requested;
    }

    /**
     * Returns requested.
     *
     * @return string[]
     */
    public function getAvailable(): array
    {
        return $this->available;
    }
}
