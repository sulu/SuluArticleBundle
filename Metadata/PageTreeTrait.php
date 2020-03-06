<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Metadata;

use Sulu\Bundle\ArticleBundle\Content\PageTreeRouteContentType;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\RoutableSubscriber;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * Encapsulates function to extract parent page from structure-metadata.
 */
trait PageTreeTrait
{
    /**
     * @return StructureMetadataFactoryInterface
     */
    abstract protected function getStructureMetadataFactory();

    /**
     * @return null|string
     */
    protected function getParentPageUuidFromPageTree(ArticleInterface $document)
    {
        $structureMetadata = $this->getStructureMetadataFactory()->getStructureMetadata(
            'article',
            $document->getStructureType()
        );

        $propertyMetadata = $this->getRoutePathProperty($structureMetadata);
        if (!$propertyMetadata) {
            return null;
        }

        $property = $document->getStructure()->getProperty($propertyMetadata->getName());
        if (!$property || PageTreeRouteContentType::NAME !== $propertyMetadata->getType()) {
            return null;
        }

        $value = $property->getValue();
        if (!$value || !isset($value['page']) || !isset($value['page']['uuid'])) {
            return null;
        }

        return $value['page']['uuid'];
    }

    /**
     * Returns property-metadata for route-path property.
     *
     * @return null|PropertyMetadata
     */
    private function getRoutePathProperty(StructureMetadata $metadata)
    {
        if ($metadata->hasTag(RoutableSubscriber::TAG_NAME)) {
            return $metadata->getPropertyByTagName(RoutableSubscriber::TAG_NAME);
        }

        if (!$metadata->hasProperty(RoutableSubscriber::ROUTE_FIELD)) {
            return null;
        }

        return $metadata->getProperty(RoutableSubscriber::ROUTE_FIELD);
    }
}
