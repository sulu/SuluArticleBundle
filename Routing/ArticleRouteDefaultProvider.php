<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Routing;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * Provides route-defaults for articles.
 */
class ArticleRouteDefaultProvider implements RouteDefaultsProviderInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureMetadataFactory
     */
    private $structureMetadataFactory;

    /**
     * @param DocumentManagerInterface $documentManager
     * @param StructureMetadataFactory $structureMetadataFactory
     */
    public function __construct(
        DocumentManagerInterface $documentManager,
        StructureMetadataFactory $structureMetadataFactory
    ) {
        $this->documentManager = $documentManager;
        $this->structureMetadataFactory = $structureMetadataFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @param ArticleDocument $object
     */
    public function getByEntity($entityClass, $id, $locale, $object = null)
    {
        if (!$object) {
            $object = $this->documentManager->find($id, $locale);
        }

        $metadata = $this->structureMetadataFactory->getStructureMetadata('article', $object->getStructureType());

        return [
            'object' => $object,
            'view' => $metadata->view,
            '_cacheLifetime' => $metadata->cacheLifetime,
            '_controller' => $metadata->controller,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entityClass)
    {
        return $entityClass === ArticleDocument::class;
    }
}
