<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\ArticleBundle\Content\PageTreeRouteContentType;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles relation between articles and pages.
 */
class PageTreeRouteSubscriber implements EventSubscriberInterface
{
    const ROUTE_PROPERTY = 'routePath';
    const TAG_NAME = 'sulu_article.article_route';

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var PropertyEncoder
     */
    protected $properyEncoder;

    /**
     * @var DocumentInspector
     */
    protected $documentInspector;

    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var SessionInterface
     */
    protected $liveSession;

    /**
     * @param DocumentManagerInterface $documentManager
     * @param PropertyEncoder $properyEncoder
     * @param DocumentInspector $documentInspector
     * @param StructureMetadataFactoryInterface $metadataFactory
     * @param SessionInterface $liveSession
     */
    public function __construct(
        DocumentManagerInterface $documentManager,
        PropertyEncoder $properyEncoder,
        DocumentInspector $documentInspector,
        StructureMetadataFactoryInterface $metadataFactory,
        SessionInterface $liveSession
    ) {
        $this->documentManager = $documentManager;
        $this->properyEncoder = $properyEncoder;
        $this->documentInspector = $documentInspector;
        $this->metadataFactory = $metadataFactory;
        $this->liveSession = $liveSession;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // should be called before the live resource-segment will be updated
            Events::PUBLISH => ['handlePublish', 10],
            // should be called after the live resource-segment was be updated
            Events::MOVE => ['handleMove', -1000],
        ];
    }

    /**
     * Update route-paths of articles which are linked to the given page-document.
     *
     * @param AbstractMappingEvent $event
     */
    public function handlePublish(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof PageDocument || !$this->hasChangedResourceSegment($document)) {
            return;
        }

        $locale = $event->getLocale();
        $articles = $this->findLinkedArticles($document->getUuid(), $locale);
        foreach ($articles as $article) {
            $this->updateArticle($article, $document->getResourceSegment(), $locale);
        }
    }

    /**
     * Update route-paths of articles which are linked to the given page-document.
     *
     * @param MoveEvent $event
     */
    public function handleMove(MoveEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof PageDocument) {
            return;
        }

        foreach ($this->documentInspector->getLocales($document) as $locale) {
            $localizedDocument = $this->documentManager->find($document->getUuid(), $locale);
            $articles = $this->findLinkedArticles($localizedDocument->getUuid(), $locale);
            foreach ($articles as $article) {
                $this->updateArticle($article, $localizedDocument->getResourceSegment(), $locale);
            }
        }
    }

    /**
     * Find articles linked to the given page.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return ArticleInterface[]
     */
    private function findLinkedArticles($uuid, $locale)
    {
        $where = [];
        foreach ($this->metadataFactory->getStructures('article') as $metadata) {
            $property = $this->getRoutePathPropertyName($metadata);
            if (null === $property || PageTreeRouteContentType::NAME !== $property->getType()) {
                continue;
            }

            $where[] = sprintf(
                '([%s] = "%s" AND [%s-page] = "%s")',
                $this->properyEncoder->localizedSystemName('template', $locale),
                $metadata->getName(),
                $this->properyEncoder->localizedSystemName($property->getName(), $locale),
                $uuid
            );
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
     * @param string $resourceSegment
     * @param string $locale
     */
    private function updateArticle(ArticleDocument $article, $resourceSegment, $locale)
    {
        $property = $this->getRoutePathPropertyNameByStructureType($article->getStructureType());
        $propertyName = $this->properyEncoder->localizedContentName($property->getName(), $locale);

        $node = $this->documentInspector->getNode($article);
        $node->setProperty($propertyName . '-page-path', $resourceSegment);

        $suffix = $node->getPropertyValueWithDefault($propertyName . '-suffix', null);
        if ($suffix) {
            $path = rtrim($resourceSegment, '/') . '/' . $suffix;
            $node->setProperty($propertyName, $path);
            $article->setRoutePath($path);
        }

        if (WorkflowStage::PUBLISHED === $article->getWorkflowStage()) {
            $this->documentManager->publish($article, $locale);
        }
    }

    /**
     * Returns true if the resource-segment was changed in the draft page.
     *
     * @param PageDocument $document
     *
     * @return bool
     */
    private function hasChangedResourceSegment(PageDocument $document)
    {
        $metadata = $this->metadataFactory->getStructureMetadata('page', $document->getStructureType());

        $urlProperty = $metadata->getPropertyByTagName('sulu.rlp');
        $urlPropertyName = $this->properyEncoder->localizedContentName($urlProperty->getName(), $document->getLocale());

        $liveNode = $this->getLiveNode($document);

        return $liveNode->getPropertyValueWithDefault($urlPropertyName, null) !== $document->getResourceSegment();
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

    /**
     * Returns the live node for given document.
     *
     * @param PathBehavior $document
     *
     * @return NodeInterface
     */
    private function getLiveNode(PathBehavior $document)
    {
        return $this->liveSession->getNode($document->getPath());
    }
}
