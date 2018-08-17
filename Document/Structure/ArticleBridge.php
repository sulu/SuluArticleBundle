<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Structure;

use Sulu\Component\Content\Compat\Structure\StructureBridge;

/**
 * Own structure bridge for articles.
 */
class ArticleBridge extends StructureBridge
{
    /**
     * @var string
     */
    private $webspaceKey = null;

    /**
     * {@inheritdoc}
     */
    public function getView()
    {
        return $this->structure->view;
    }

    /**
     * {@inheritdoc}
     *
     * Will be called by SuluCollector to collect profiler data.
     */
    public function getNavContexts()
    {
        return null;
    }

    public function getEnabledShadowLanguages()
    {
        return $this->inspector->getShadowLocales($this->getDocument());
    }

    public function getConcreteLanguages()
    {
        return $this->inspector->getConcreteLocales($this->getDocument());
    }

    /**
     * {@inheritdoc}
     *
     * Will be called by SuluCollector to collect profiler data.
     */
    public function getOriginTemplate()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebspaceKey($webspace)
    {
        $this->webspaceKey = $webspace;
    }
}
