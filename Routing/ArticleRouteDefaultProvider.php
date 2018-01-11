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
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\HttpCache\CacheLifetimeResolverInterface;

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
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @param DocumentManagerInterface $documentManager
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     * @param CacheLifetimeResolverInterface $cacheLifetimeResolver
     * @param StructureManagerInterface $structureManager
     */
    public function __construct(
        DocumentManagerInterface $documentManager,
        StructureMetadataFactoryInterface $structureMetadataFactory,
        CacheLifetimeResolverInterface $cacheLifetimeResolver,
        StructureManagerInterface $structureManager
    ) {
        $this->documentManager = $documentManager;
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->cacheLifetimeResolver = $cacheLifetimeResolver;
        $this->structureManager = $structureManager;
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

        $pageNumber = $object->getPageNumber();
        if ($object instanceof ArticlePageDocument) {
            // the article contains the seo/excerpt data and the controller handles the page-number automatically
            $object = $object->getParent();
        }

        $metadata = $this->structureMetadataFactory->getStructureMetadata('article', $object->getStructureType());

        // this parameter should not be used
        // but the sulu-collector for the profiler needs it to determine data from request
        $structure = $this->structureManager->wrapStructure('article', $metadata);
        $structure->setDocument($object);

        return [
            'object' => $object,
            'view' => $metadata->view,
            'pageNumber' => $pageNumber,
            'structure' => $structure,
            '_cacheLifetime' => $this->getCacheLifetime($metadata),
            '_controller' => $metadata->controller,
        ];
    }

    /**
     * If article is not published the document will be of typ unknown-document.
     * Also check the workflow stage if it`s a ArticleDocument.
     *
     * {@inheritdoc}
     */
    public function isPublished($entityClass, $id, $locale)
    {
        $object = $this->documentManager->find($id, $locale);

        return $object instanceof ArticleInterface && WorkflowStage::PUBLISHED === $object->getWorkflowStage();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entityClass)
    {
        return ArticleDocument::class === $entityClass
            || ArticlePageDocument::class === $entityClass
            || is_subclass_of($entityClass, ArticleDocument::class)
            || is_subclass_of($entityClass, ArticlePageDocument::class);
    }

    /**
     * Get cache life time.
     *
     * @param StructureMetadata $metadata
     *
     * @return int|null
     */
    private function getCacheLifetime($metadata)
    {
        $cacheLifetime = $metadata->cacheLifetime;

        if (!$cacheLifetime) {
            return null;
        }

        if (!is_array($cacheLifetime)
            || !isset($cacheLifetime['type'])
            || !isset($cacheLifetime['value'])
            || !$this->cacheLifetimeResolver->supports($cacheLifetime['type'], $cacheLifetime['value'])
        ) {
            throw new \InvalidArgumentException(
                sprintf('Invalid cachelifetime in article route default provider: %s', var_export($cacheLifetime, true))
            );
        }

        return $this->cacheLifetimeResolver->resolve($cacheLifetime['type'], $cacheLifetime['value']);
    }
}
