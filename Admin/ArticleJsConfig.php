<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\JsConfigInterface;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleTypeTrait;
use Sulu\Component\Content\Compat\StructureManagerInterface;

/**
 * Provides js-configuration.
 */
class ArticleJsConfig implements JsConfigInterface
{
    use ArticleTypeTrait;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var array
     */
    private $typeConfiguration;

    /**
     * @var bool
     */
    private $displayTabAll;

    /**
     * @param StructureManagerInterface $structureManager
     * @param array $typeConfiguration
     * @param bool $displayTabAll
     */
    public function __construct(
        StructureManagerInterface $structureManager,
        array $typeConfiguration,
        $displayTabAll
    ) {
        $this->structureManager = $structureManager;
        $this->typeConfiguration = $typeConfiguration;
        $this->displayTabAll = $displayTabAll;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        $config = [];

        $config['types'] = [];
        foreach ($this->structureManager->getStructures('article') as $structure) {
            $type = $this->getType($structure->getStructure());
            if (!array_key_exists($type, $config['types'])) {
                $config['types'][$type] = [
                    'default' => $structure->getKey(),
                    'title' => $this->getTitle($type),
                ];
            }
        }

        $config['displayTabAll'] = $this->displayTabAll;

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_article';
    }

    /**
     * Returns title for given type.
     *
     * @param string $type
     *
     * @return string
     */
    private function getTitle($type)
    {
        if (!array_key_exists($type, $this->typeConfiguration)) {
            return ucfirst($type);
        }

        return $this->typeConfiguration[$type]['translation_key'];
    }
}
