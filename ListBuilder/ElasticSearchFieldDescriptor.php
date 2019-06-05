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
    public static function create(string $name, string $translation = null)
    {
        return new ElasticSearchFieldDescriptorBuilder($name, $translation);
    }

    /**
     * @var string
     */
    private $sortField;

    public function __construct(
        string $name,
        string $sortField = null,
        string $translation = null,
        string $visibility = FieldDescriptorInterface::VISIBILITY_YES,
        string $searchability = FieldDescriptorInterface::SEARCHABILITY_NEVER,
        string $type = '',
        string $width = '',
        string $minWidth = '',
        bool $sortable = true,
        bool $editable = false,
        string $cssClass = ''
    ) {
        $this->sortField = $sortField ? $sortField : $name;

        parent::__construct(
            $name,
            $translation,
            $visibility,
            $searchability,
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
