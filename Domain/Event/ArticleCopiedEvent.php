<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;

class ArticleCopiedEvent extends DomainEvent
{
    /**
     * @var ArticleDocument
     */
    private $articleDocument;

    /**
     * @var string
     */
    private $sourceArticleId;

    /**
     * @var string|null
     */
    private $sourceArticleTitle;

    /**
     * @var string|null
     */
    private $sourceArticleTitleLocale;

    public function __construct(
        ArticleDocument $articleDocument,
        string $sourceArticleId,
        ?string $sourceArticleTitle,
        ?string $sourceArticleTitleLocale
    ) {
        parent::__construct();

        $this->articleDocument = $articleDocument;
        $this->sourceArticleId = $sourceArticleId;
        $this->sourceArticleTitle = $sourceArticleTitle;
        $this->sourceArticleTitleLocale = $sourceArticleTitleLocale;
    }

    public function getArticleDocument(): ArticleDocument
    {
        return $this->articleDocument;
    }

    public function getEventType(): string
    {
        return 'copied';
    }

    public function getEventContext(): array
    {
        return [
            'sourceArticleId' => $this->sourceArticleId,
            'sourceArticleTitle' => $this->sourceArticleTitle,
            'sourceArticleTitleLocale' => $this->sourceArticleTitleLocale,
        ];
    }

    public function getResourceKey(): string
    {
        return ArticleDocument::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->articleDocument->getUuid();
    }

    public function getResourceTitle(): ?string
    {
        return $this->articleDocument->getTitle();
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->articleDocument->getLocale();
    }

    public function getResourceSecurityContext(): ?string
    {
        return ArticleAdmin::SECURITY_CONTEXT;
    }
}
