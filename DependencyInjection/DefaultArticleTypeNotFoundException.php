<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\DependencyInjection;

/**
 * Will be raised when the default template for article wont be found.
 */
class DefaultArticleTypeNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $structureType;

    public function __construct(string $type, string $structureType, \Exception $previous)
    {
        parent::__construct(sprintf('Default "%s" template "%s" was not found.', $type, $structureType), 0, $previous);

        $this->structureType = $structureType;
        $this->type = $type;
    }

    /**
     * Returns type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns structure-type.
     */
    public function getStructureType(): string
    {
        return $this->structureType;
    }
}
