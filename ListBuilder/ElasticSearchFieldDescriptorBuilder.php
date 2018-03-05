<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\ListBuilder;

final class ElasticSearchFieldDescriptorBuilder
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $translation;

    /**
     * @var string
     */
    private $sortField = null;

    /**
     * @var bool
     */
    private $disabled = false;

    /**
     * @var bool
     */
    private $default = false;

    /**
     * @var string
     */
    private $type = 'string';

    /**
     * @var bool
     */
    private $sortable = false;

    public function __construct($name, $translation)
    {
        $this->name = $name;
        $this->translation = $translation;
    }

    public function setSortField($sortField)
    {
        $this->sortField = $sortField;
        $this->sortable = true;

        return $this;
    }

    public function setDisable($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function build()
    {
        return new ElasticSearchFieldDescriptor(
            $this->name,
            $this->sortField,
            $this->translation,
            $this->disabled,
            $this->default,
            $this->type,
            '',
            '',
            $this->sortable
        );
    }
}
