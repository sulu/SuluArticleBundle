<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleTypeTrait;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;

/**
 * Extends serialization for articles.
 */
class ArticleSubscriber implements EventSubscriberInterface
{
    const PAGE_TITLE_TAG_NAME = 'sulu_article.page_title';
    const PAGE_TITLE_PROPERTY_NAME = 'pageTitle';

    use ArticleTypeTrait;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @param StructureManagerInterface $structureManager
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     */
    public function __construct(
        StructureManagerInterface $structureManager,
        StructureMetadataFactoryInterface $structureMetadataFactory
    ) {
        $this->structureManager = $structureManager;
        $this->structureMetadataFactory = $structureMetadataFactory;
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
                'method' => 'addPageTitlePropertyNameOnPostSerialize',
            ],
        ];
    }

    /**
     * Append type to result.
     *
     * @param ObjectEvent $event
     */
    public function addTypeOnPostSerialize(ObjectEvent $event)
    {
        $article = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!($article instanceof ArticleDocument)) {
            return;
        }

        $structure = $this->structureManager->getStructure($article->getStructureType(), 'article');
        $visitor->addData('articleType', $context->accept($this->getType($structure->getStructure())));
    }

    /**
     * Append page-title-property to result.
     *
     * @param ObjectEvent $event
     */
    public function addPageTitlePropertyNameOnPostSerialize(ObjectEvent $event)
    {
        $article = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!$article instanceof ArticleInterface) {
            return;
        }

        $property = $this->getPageTitleProperty($article);
        if ($property) {
            $visitor->addData('_pageTitlePropertyName', $context->accept($property->getName()));
        }
    }

    /**
     * Find page-title property.
     *
     * @param ArticleInterface $document
     *
     * @return PropertyMetadata
     */
    private function getPageTitleProperty(ArticleInterface $document)
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
