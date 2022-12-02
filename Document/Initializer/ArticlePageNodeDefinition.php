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

use PHPCR\NodeType\NodeDefinitionInterface;
use PHPCR\Version\OnParentVersionAction;

/**
 * Represents definition for article-pages as children for article-document.
 */
class ArticlePageNodeDefinition implements NodeDefinitionInterface
{
    public function getDeclaringNodeType()
    {
        return null;
    }

    public function getName()
    {
        return '*';
    }

    public function isAutoCreated()
    {
        return false;
    }

    public function isMandatory()
    {
        return false;
    }

    public function getOnParentVersion()
    {
        return OnParentVersionAction::COPY;
    }

    public function isProtected()
    {
        return false;
    }

    public function getRequiredPrimaryTypes()
    {
        return null;
    }

    public function getRequiredPrimaryTypeNames()
    {
        return null;
    }

    public function getDefaultPrimaryType()
    {
        return null;
    }

    public function getDefaultPrimaryTypeName()
    {
        return null;
    }

    public function allowsSameNameSiblings()
    {
        return false;
    }
}
