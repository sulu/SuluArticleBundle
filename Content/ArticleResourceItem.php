<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Content;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\ExcerptViewObject;
use Sulu\Bundle\ArticleBundle\Document\SeoViewObject;
use Sulu\Component\SmartContent\ResourceItemInterface;

/**
 * Represents a resource-item for smart-content data-provider.
 */
class ArticleResourceItem implements ResourceItemInterface
{
    /**
     * @var ArticleViewDocumentInterface
     */
    private $article;

    /**
     * @var ArticleDocument
     */
    private $resource;

    public function __construct(ArticleViewDocumentInterface $article, ArticleDocument $resource)
    {
        $this->article = $article;
        $this->resource = $resource;
    }

    /**
     * Returns uuid.
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->article->getUuid();
    }

    /**
     * Returns locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->article->getLocale();
    }

    /**
     * Returns title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->article->getTitle();
    }

    /**
     * Returns type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->article->getType();
    }

    /**
     * Returns changer.
     *
     * @return string
     */
    public function getChanger()
    {
        return $this->article->getChangerFullName();
    }

    /**
     * Returns creator.
     *
     * @return string
     */
    public function getCreator()
    {
        return $this->article->getCreatorFullName();
    }

    /**
     * Return changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->article->getChanged();
    }

    /**
     * Returns created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->article->getCreated();
    }

    /**
     * Returns published.
     *
     * @return \DateTime
     */
    public function getPublished()
    {
        return $this->article->getPublished();
    }

    /**
     * Returns authored.
     *
     * @return \DateTime
     */
    public function getAuthored()
    {
        return $this->article->getAuthored();
    }

    /**
     * Returns excerpt.
     *
     * @return ExcerptViewObject
     */
    public function getExcerpt()
    {
        return $this->article->getExcerpt();
    }

    /**
     * Returns seo.
     *
     * @return SeoViewObject
     */
    public function getSeo()
    {
        return $this->article->getSeo();
    }

    /**
     * Returns route-path.
     *
     * @return string
     */
    public function getRoutePath()
    {
        return $this->article->getRoutePath();
    }

    /**
     * Returns view-object.
     *
     * @return ArticleViewDocumentInterface
     */
    public function getContent()
    {
        return $this->article;
    }

    /**
     * @return string
     */
    public function getTargetWebspace()
    {
        return $this->article->getTargetWebspace();
    }

    /**
     * @return string
     */
    public function getMainWebspace()
    {
        return $this->article->getMainWebspace();
    }

    /**
     * @return null|string[]
     */
    public function getAdditionalWebspaces()
    {
        return $this->article->getAdditionalWebspaces();
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getUuid();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorId()
    {
        return $this->article->getAuthorId();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorFullName()
    {
        return $this->article->getAuthorFullName();
    }
}
