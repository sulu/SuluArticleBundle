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

class ArticleRemovedEvent extends DomainEvent
{
    /**
     * @var string
     */
    private $articleId;

    /**
     * @var string|null
     */
    private $articleTitle;

    /**
     * @var string|null
     */
    private $articleTitleLocale;

    public function __construct(string $articleId, ?string $articleTitle, ?string $articleTitleLocale)
    {
        parent::__construct();

        $this->articleId = $articleId;
        $this->articleTitle = $articleTitle;
        $this->articleTitleLocale = $articleTitleLocale;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return ArticleDocument::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->articleId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->articleTitle;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->articleTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ArticleAdmin::SECURITY_CONTEXT;
    }
}
