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

use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

class ArticleCreatedEvent extends DomainEvent
{
    /**
     * @var ArticleDocument
     */
    private $articleDocument;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var mixed[]
     */
    private $payload;

    /**
     * @param mixed[] $payload
     */
    public function __construct(ArticleDocument $articleDocument, string $locale, array $payload)
    {
        parent::__construct();

        $this->articleDocument = $articleDocument;
        $this->locale = $locale;
        $this->payload = $payload;
    }

    public function getArticleDocument(): ArticleDocument
    {
        return $this->articleDocument;
    }

    public function getEventType(): string
    {
        return 'created';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return ArticleDocument::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->articleDocument->getUuid();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
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
