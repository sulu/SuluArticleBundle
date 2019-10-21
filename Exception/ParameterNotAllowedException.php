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
 * Thrown when parameter is not allowed.
 */
class ParameterNotAllowedException extends \Exception
{
    /**
     * @var string
     */
    private $property;

    /**
     * @var string
     */
    private $class;

    /**
     * @param string $property
     * @param string $class
     */
    public function __construct($property, $class)
    {
        parent::__construct(sprintf('Parameter "%s" is not allowed for class "%s".', $property, $class));

        $this->property = $property;
        $this->class = $class;
    }

    /**
     * Returns property.
     *
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Returns class.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
