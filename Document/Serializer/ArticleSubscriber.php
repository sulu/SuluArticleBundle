<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Resolver\WebspaceResolver;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;

/**
 * Extends serialization for articles.
 */
class ArticleSubscriber implements EventSubscriberInterface
{
    use StructureTagTrait;

    const PAGE_TITLE_TAG_NAME = 'sulu_article.page_title';
    const PAGE_TITLE_PROPERTY_NAME = 'pageTitle';

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var WebspaceResolver
     */
    private $webspaceResolver;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    public function __construct(
        StructureManagerInterface $structureManager,
        StructureMetadataFactoryInterface $structureMetadataFactory,
        WebspaceResolver $webspaceResolver
    ) {
        $this->structureManager = $structureManager;
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->webspaceResolver = $webspaceResolver;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'addTypeOnPostSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'addWebspaceSettingsOnPostSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'addBrokenIndicatorOnPostSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'addPageTitlePropertyNameOnPostSerialize',
            ],
        ];
    }

    /**
     * Append type to result.
     */
    public function addTypeOnPostSerialize(ObjectEvent $event): void
    {
        $article = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        if (!($article instanceof ArticleDocument)) {
            return;
        }

        /** @var StructureBridge $structure */
        $structure = $this->structureManager->getStructure($article->getStructureType(), 'article');

        $articleType = $this->getType($structure->getStructure());
        $visitor->visitProperty(new StaticPropertyMetadata('', 'articleType', $articleType), $articleType);
    }

    /**
     * Append webspace-settings to result.
     */
    public function addWebspaceSettingsOnPostSerialize(ObjectEvent $event): void
    {
        $article = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        if (!($article instanceof ArticleDocument)) {
            return;
        }

        $customizeWebspaceSettings = (null !== $article->getMainWebspace());
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'customizeWebspaceSettings', $customizeWebspaceSettings),
            $customizeWebspaceSettings
        );
        if ($article->getMainWebspace()) {
            return;
        }

        $mainWebspace = $this->webspaceResolver->resolveMainWebspace($article);
        $visitor->visitProperty(new StaticPropertyMetadata('', 'mainWebspace', $mainWebspace), $mainWebspace);

        $additionalWebspace = $this->webspaceResolver->resolveAdditionalWebspaces($article);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'additionalWebspaces', $additionalWebspace),
            $additionalWebspace
        );
    }

    /**
     * Append broken-indicator to result.
     */
    public function addBrokenIndicatorOnPostSerialize(ObjectEvent $event): void
    {
        $article = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        if (!($article instanceof ArticleViewDocumentInterface)) {
            return;
        }

        $structure = $this->structureManager->getStructure($article->getStructureType(), 'article');

        $broken = (!$structure || $structure->getKey() !== $article->getStructureType());
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'broken', $broken),
            $broken
        );

        $originalStructureType = $article->getStructureType();
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'originalStructureType', $originalStructureType),
            $originalStructureType
        );
    }

    /**
     * Append page-title-property to result.
     */
    public function addPageTitlePropertyNameOnPostSerialize(ObjectEvent $event): void
    {
        $article = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        if (!$article instanceof ArticleInterface) {
            return;
        }

        $property = $this->getPageTitleProperty($article);
        if ($property) {
            $propertyName = $property->getName();
            $visitor->visitProperty(
                new StaticPropertyMetadata('', '_pageTitlePropertyName', $propertyName),
                $propertyName
            );
        }
    }

    /**
     * Find page-title property.
     */
    private function getPageTitleProperty(ArticleInterface $document): ?PropertyMetadata
    {
        $metadata = $this->structureMetadataFactory->getStructureMetadata(
            'article',
            $document->getStructureType()
        );

        if ($metadata->hasPropertyWithTagName(self::PAGE_TITLE_TAG_NAME)) {
            return $metadata->getPropertyByTagName(self::PAGE_TITLE_TAG_NAME);
        }

        if ($metadata->hasProperty(self::PAGE_TITLE_PROPERTY_NAME)) {
            return $metadata->getProperty(self::PAGE_TITLE_PROPERTY_NAME);
        }

        return null;
    }
}
