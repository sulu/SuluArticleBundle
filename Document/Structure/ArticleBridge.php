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
     * @var string
     */
    private $uuid;

    /**
     * {@inheritdoc}
     */
    public function getView()
    {
        return $this->structure->view;
    }

    public function getUuid()
    {
        // is set for structure loaded with document from document-manager
        // is not set when using structure with view-document
        if ($this->document) {
            return parent::getUuid();
        }

        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
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

    public function getExt()
    {
        return $this->document->getExtensionsData();
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
