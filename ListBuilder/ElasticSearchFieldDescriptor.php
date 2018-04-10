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

use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

/**
 * Extends the default FieldDescriptor with the property sort field to configure it.
 * Default is the name.
 */
class ElasticSearchFieldDescriptor extends FieldDescriptor
{
    public static function create($name, $translation = null)
    {
        return new ElasticSearchFieldDescriptorBuilder($name, $translation);
    }

    /**
     * @var string
     */
    private $sortField;

    public function __construct(
        $name,
        $sortField = null,
        $translation = null,
        $visibility = FieldDescriptorInterface::VISIBILITY_NO,
        $type = '',
        $width = '',
        $minWidth = '',
        $sortable = true,
        $editable = false,
        $cssClass = ''
    ) {
        $this->sortField = $sortField ? $sortField : $name;

        parent::__construct(
            $name,
            $translation,
            $visibility,
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
