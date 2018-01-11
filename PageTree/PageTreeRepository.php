<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\PageTree;

use Sulu\Bundle\ArticleBundle\Content\PageTreeRouteContentType;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;

/**
 * Update the route of articles synchronously.
 */
class PageTreeRepository implements PageTreeUpdaterInterface, PageTreeMoverInterface
{
    const ROUTE_PROPERTY = 'routePath';

    const TAG_NAME = 'sulu_article.article_route';

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var PropertyEncoder
     */
    protected $propertyEncoder;

    /**
     * @var DocumentInspector
     */
    protected $documentInspector;

    /**
     * @param DocumentManagerInterface $documentManager
     * @param StructureMetadataFactoryInterface $metadataFactory
     * @param PropertyEncoder $propertyEncoder
     * @param DocumentInspector $documentInspector
     */
    public function __construct(
        DocumentManagerInterface $documentManager,
        StructureMetadataFactoryInterface $metadataFactory,
        PropertyEncoder $propertyEncoder,
        DocumentInspector $documentInspector
    ) {
        $this->documentManager = $documentManager;
        $this->metadataFactory = $metadataFactory;
        $this->propertyEncoder = $propertyEncoder;
        $this->documentInspector = $documentInspector;
    }

    /**
     * {@inheritdoc}
     */
    public function update(BasePageDocument $document)
    {
        $articles = $this->findLinkedArticles('page', $document->getUuid(), $document->getLocale());
        foreach ($articles as $article) {
            $this->updateArticle($article, $document);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function move($source, BasePageDocument $document)
    {
        $articles = $this->findLinkedArticles('page-path', $source, $document->getLocale());
        foreach ($articles as $article) {
            $this->updateArticle($article, $document);
        }
    }

    /**
     * Find articles linked to the given page.
     *
     * @param string $field
     * @param string $value
     * @param string $locale
     *
     * @return ArticleInterface[]
     */
    private function findLinkedArticles($field, $value, $locale)
    {
        $where = [];
        foreach ($this->metadataFactory->getStructures('article') as $metadata) {
            $property = $this->getRoutePathPropertyName($metadata);
            if (null === $property || PageTreeRouteContentType::NAME !== $property->getType()) {
                continue;
            }

            $where[] = sprintf(
                '([%s] = "%s" AND [%s-%s] = "%s")',
                $this->propertyEncoder->localizedSystemName('template', $locale),
                $metadata->getName(),
                $this->propertyEncoder->localizedContentName($property->getName(), $locale),
                $field,
                $value
            );
        }

        if (0 === count($where)) {
            return [];
        }

        $query = $this->documentManager->createQuery(
            sprintf(
                'SELECT * FROM [nt:unstructured] WHERE [jcr:mixinTypes] = "sulu:article" AND (%s)',
                implode(' OR ', $where)
            ),
            $locale
        );

        return $query->execute();
    }

    /**
     * Update route of given article.
     *
     * @param ArticleDocument $article
     * @param BasePageDocument $document
     */
    private function updateArticle(ArticleDocument $article, BasePageDocument $document)
    {
        $locale = $document->getLocale();
        $resourceSegment = $document->getResourceSegment();

        $property = $this->getRoutePathPropertyNameByStructureType($article->getStructureType());
        $propertyName = $this->propertyEncoder->localizedContentName($property->getName(), $locale);

        $node = $this->documentInspector->getNode($article);
        $node->setProperty($propertyName . '-page', $document->getUuid());
        $node->setProperty($propertyName . '-page-path', $resourceSegment);

        $suffix = $node->getPropertyValueWithDefault($propertyName . '-suffix', null);
        if ($suffix) {
            $path = rtrim($resourceSegment, '/') . '/' . $suffix;
            $node->setProperty($propertyName, $path);
            $article->setRoutePath($path);
        }

        $workflowStage = $article->getWorkflowStage();

        $this->documentManager->persist($article, $locale);
        if (WorkflowStage::PUBLISHED === $workflowStage) {
            $this->documentManager->publish($article, $locale);
        }
    }

    /**
     * Returns "routePath" property.
     *
     * @param string $structureType
     *
     * @return PropertyMetadata
     */
    private function getRoutePathPropertyNameByStructureType($structureType)
    {
        $metadata = $this->metadataFactory->getStructureMetadata('article', $structureType);
        if ($metadata->hasTag(self::TAG_NAME)) {
            return $metadata->getPropertyByTagName(self::TAG_NAME);
        }

        return $metadata->getProperty(self::ROUTE_PROPERTY);
    }

    /**
     * Returns "routePath" property.
     *
     * @param StructureMetadata $metadata
     *
     * @return PropertyMetadata
     */
    private function getRoutePathPropertyName(StructureMetadata $metadata)
    {
        if ($metadata->hasTag(self::TAG_NAME)) {
            return $metadata->getPropertyByTagName(self::TAG_NAME);
        }

        if (!$metadata->hasProperty(self::ROUTE_PROPERTY)) {
            return;
        }

        return $metadata->getProperty(self::ROUTE_PROPERTY);
    }
}
