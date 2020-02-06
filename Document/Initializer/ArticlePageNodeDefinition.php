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
    /**
     * {@inheritdoc}
     */
    public function getDeclaringNodeType()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '*';
    }

    /**
     * {@inheritdoc}
     */
    public function isAutoCreated()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isMandatory()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getOnParentVersion()
    {
        return OnParentVersionAction::COPY;
    }

    /**
     * {@inheritdoc}
     */
    public function isProtected()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPrimaryTypes()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPrimaryTypeNames()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPrimaryType()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPrimaryTypeName()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function allowsSameNameSiblings()
    {
        return false;
    }
}
