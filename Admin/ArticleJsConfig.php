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
use Sulu\Bundle\ArticleBundle\Util\TypeTrait;
use Sulu\Component\Content\Compat\StructureManagerInterface;

/**
 * Provides js-configuration.
 */
class ArticleJsConfig implements JsConfigInterface
{
    use TypeTrait;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @param StructureManagerInterface $structureManager
     */
    public function __construct(StructureManagerInterface $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        $types = [];
        foreach ($this->structureManager->getStructures('article') as $structure) {
            $type = $this->getType($structure->getStructure());
            if (!array_key_exists($type, $types)) {
                $types[$type] = $structure->getKey();
            }
        }

        return $types;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_article.types';
    }
}
