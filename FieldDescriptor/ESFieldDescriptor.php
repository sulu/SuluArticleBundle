<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\FieldDescriptor;

use Sulu\Component\Rest\ListBuilder\FieldDescriptor;

/**
 * Extends the default FieldDescriptor with the property sort field to configure it.
 * Default is the name.
 */
class ESFieldDescriptor extends FieldDescriptor
{
    /**
     * @var string
     */
    private $sortField;

    public function __construct(
        $name,
        $sortField = '',
        $translation = null,
        $disabled = false,
        $default = false,
        $type = '',
        $width = '',
        $minWidth = '',
        $sortable = true,
        $editable = false,
        $cssClass = ''
    ) {
        if ('' !== $sortField) {
            $this->sortField = $sortField;
        } else {
            $this->sortField = $name;
        }

        parent::__construct(
            $name,
            $translation,
            $disabled,
            $default,
            $type,
            $width,
            $minWidth,
            $sortable,
            $editable,
            $cssClass
        );
    }

    /**
     * @return string
     */
    public function getSortField()
    {
        return $this->sortField;
    }
}
