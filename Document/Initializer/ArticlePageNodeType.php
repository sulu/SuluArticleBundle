<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Initializer;

use PHPCR\NodeType\NodeTypeDefinitionInterface;

/**
 * Node type for article-page phpcr-nodes.
 */
class ArticlePageNodeType implements NodeTypeDefinitionInterface
{
    public function getName()
    {
        return 'sulu:articlepage';
    }

    public function getDeclaredSupertypeNames()
    {
        return [
            'sulu:article',
        ];
    }

    public function isAbstract()
    {
        return false;
    }

    public function isMixin()
    {
        return true;
    }

    public function hasOrderableChildNodes()
    {
        return false;
    }

    public function isQueryable()
    {
        return false;
    }

    public function getPrimaryItemName()
    {
        return;
    }

    public function getDeclaredPropertyDefinitions()
    {
        return [];
    }

    public function getDeclaredChildNodeDefinitions()
    {
        return [];
    }
}
