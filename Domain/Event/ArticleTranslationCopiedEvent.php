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

class ArticleTranslationCopiedEvent extends DomainEvent
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
     * @var string
     */
    private $sourceLocale;

    /**
     * @var mixed[]
     */
    private $payload;

    /**
     * @param mixed[] $payload
     */
    public function __construct(
        ArticleDocument $articleDocument,
        string $locale,
        string $sourceLocale,
        array $payload
    ) {
        parent::__construct();

        $this->articleDocument = $articleDocument;
        $this->locale = $locale;
        $this->sourceLocale = $sourceLocale;
        $this->payload = $payload;
    }

    public function getArticleDocument(): ArticleDocument
    {
        return $this->articleDocument;
    }

    public function getEventType(): string
    {
        return 'translation_copied';
    }

    public function getEventContext(): array
    {
        return [
            'sourceLocale' => $this->sourceLocale,
        ];
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
