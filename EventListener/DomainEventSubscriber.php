<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\EventListener;

use PHPCR\NodeInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleCopiedEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleCreatedEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleDraftRemovedEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleModifiedEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticlePublishedEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleRemovedEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleTranslationAddedEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleTranslationCopiedEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleTranslationRemovedEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleUnpublishedEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleVersionRestoredEvent;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\Content\Document\Subscriber\StructureSubscriber;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\CopyLocaleEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
class DomainEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentDomainEventCollectorInterface
     */
    private $domainEventCollector;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var array<array<string, mixed>>
     */
    private $eventsToBeDispatchedAfterFlush = [];

    /**
     * @var array<string, bool>
     */
    private $persistEventsWithNewDocument = [];

    /**
     * @var array<string, bool>
     */
    private $persistEventsWithNewLocale = [];

    public function __construct(
        DocumentDomainEventCollectorInterface $domainEventCollector,
        DocumentManagerInterface $documentManager,
        PropertyEncoder $propertyEncoder
    ) {
        $this->domainEventCollector = $domainEventCollector;
        $this->documentManager = $documentManager;
        $this->propertyEncoder = $propertyEncoder;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::FLUSH => 'handleFlush',
            Events::PERSIST => [
                ['handlePrePersist', 479], // Priority needs to be lower than AutoNameSubscriber::handlePersist (480)
                ['handlePersist', -10000],
            ],
            Events::REMOVE => ['handleRemove', -10000],
            Events::REMOVE_LOCALE => ['handleRemoveLocale', -10000],
            Events::COPY_LOCALE => ['handleCopyLocale', -10000],
            Events::COPY => ['handleCopy', -10000],
            Events::PUBLISH => ['handlePublish', -10000],
            Events::UNPUBLISH => ['handleUnpublish', -10000],
            Events::REMOVE_DRAFT => ['handleRemoveDraft', -10000],
            Events::RESTORE => ['handleRestore', -10000],
        ];
    }

    public function handleFlush(FlushEvent $event): void
    {
        $eventsToBeDispatched = $this->eventsToBeDispatchedAfterFlush;

        if (0 === \count($eventsToBeDispatched)) {
            return;
        }

        $this->eventsToBeDispatchedAfterFlush = [];

        foreach ($eventsToBeDispatched as $eventConfig) {
            $type = $eventConfig['type'] ?? null;
            $options = $eventConfig['options'] ?? [];

            switch ($type) {
                case ArticleCopiedEvent::class:
                    $articlePath = $options['articlePath'] ?? null;
                    Assert::notNull($articlePath);
                    $locale = $options['locale'] ?? null;
                    Assert::notNull($locale);
                    $sourceArticleId = $options['sourceArticleId'] ?? null;
                    Assert::notNull($sourceArticleId);
                    $sourceArticleTitle = $options['sourceArticleTitle'] ?? null;
                    Assert::notNull($sourceArticleTitle);

                    /** @var ArticleDocument $document */
                    $document = $this->documentManager->find($articlePath, $locale);

                    $this->domainEventCollector->collect(
                        new ArticleCopiedEvent(
                            $document,
                            $sourceArticleId,
                            $sourceArticleTitle,
                            $locale
                        )
                    );

                    $this->documentManager->flush();

                    break;
            }
        }
    }

    public function handlePrePersist(PersistEvent $event): void
    {
        if (!$event->hasNode()) {
            return;
        }

        /** @var string|null $locale */
        $locale = $event->getLocale();
        $node = $event->getNode();

        if (null === $locale) {
            return;
        }

        $eventHash = \spl_object_hash($event);

        if ($this->isNewNode($node)) {
            $this->persistEventsWithNewDocument[$eventHash] = true;

            return;
        }

        if ($this->isNewTranslation($node, $locale)) {
            $this->persistEventsWithNewLocale[$eventHash] = true;
        }
    }

    public function handlePersist(PersistEvent $event): void
    {
        if (true === $event->getOption('omit_modified_domain_event')) {
            return;
        }

        /** @var string|null $locale */
        $locale = $event->getLocale();
        $document = $event->getDocument();

        if (null === $locale || !$document instanceof ArticleDocument) {
            return;
        }

        $payload = $this->getPayloadFromArticleDocument($document);

        $eventHash = \spl_object_hash($event);

        if (true === ($this->persistEventsWithNewDocument[$eventHash] ?? null)) {
            unset($this->persistEventsWithNewDocument[$eventHash]);

            $this->domainEventCollector->collect(
                new ArticleCreatedEvent($document, $locale, $payload)
            );

            return;
        }

        if (true === ($this->persistEventsWithNewLocale[$eventHash] ?? null)) {
            unset($this->persistEventsWithNewLocale[$eventHash]);

            $this->domainEventCollector->collect(
                new ArticleTranslationAddedEvent($document, $locale, $payload)
            );

            return;
        }

        $this->domainEventCollector->collect(
            new ArticleModifiedEvent($document, $locale, $payload)
        );
    }

    public function handleRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new ArticleRemovedEvent(
                $document->getUuid(),
                $document->getTitle(),
                $document->getLocale()
            )
        );
    }

    public function handleRemoveLocale(RemoveLocaleEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new ArticleTranslationRemovedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleCopyLocale(CopyLocaleEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof ArticleDocument) {
            return;
        }

        $destDocument = $event->getDestDocument();

        if (!$destDocument instanceof ArticleDocument) {
            return;
        }

        $destLocale = $event->getDestLocale();
        $sourceLocale = $event->getLocale();
        $payload = $this->getPayloadFromArticleDocument($destDocument);

        $this->domainEventCollector->collect(
            new ArticleTranslationCopiedEvent(
                $destDocument,
                $destLocale,
                $sourceLocale,
                $payload
            )
        );
    }

    public function handleCopy(CopyEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->eventsToBeDispatchedAfterFlush[] = [
            'type' => ArticleCopiedEvent::class,
            'options' => [
                'articlePath' => $event->getCopiedPath(),
                'locale' => $document->getLocale(),
                'sourceArticleId' => $document->getUuid(),
                'sourceArticleTitle' => $document->getTitle(),
            ],
        ];
    }

    public function handlePublish(PublishEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new ArticlePublishedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleUnpublish(UnpublishEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new ArticleUnpublishedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleRemoveDraft(RemoveDraftEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new ArticleDraftRemovedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleRestore(RestoreEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();
        $version = $event->getVersion();

        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new ArticleVersionRestoredEvent(
                $document,
                $locale,
                $version
            )
        );
    }

    /**
     * @return mixed[]
     */
    private function getPayloadFromArticleDocument(ArticleDocument $articleDocument): array
    {
        $data = $articleDocument->getStructure()->toArray();

        /** @var ExtensionContainer|mixed[] $extensionData */
        $extensionData = $articleDocument->getExtensionsData();

        if ($extensionData instanceof ExtensionContainer) {
            $extensionData = $extensionData->toArray();
        }

        $data['ext'] = $extensionData;

        return $data;
    }

    /**
     * @param NodeInterface<mixed> $node
     *
     * @see SecuritySubscriber::handlePersistCreate()
     */
    private function isNewNode(NodeInterface $node): bool
    {
        /** @var \Countable $properties */
        $properties = $node->getProperties(
            $this->propertyEncoder->encode(
                'system_localized',
                StructureSubscriber::STRUCTURE_TYPE_FIELD,
                '*'
            )
        );

        return 0 === \count($properties);
    }

    /**
     * @param NodeInterface<mixed> $node
     */
    private function isNewTranslation(NodeInterface $node, string $locale): bool
    {
        /** @var \Countable $localizedProperties */
        $localizedProperties = $node->getProperties(
            $this->propertyEncoder->localizedContentName('*', $locale)
        );

        return 0 === \count($localizedProperties);
    }
}
