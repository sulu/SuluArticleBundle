<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Content;

use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;

/**
 * Provides page_tree_route content-type.
 */
class PageTreeRouteContentType extends SimpleContentType
{
    const NAME = 'page_tree_route';

    /**
     * @var string
     */
    private $template;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainRouteGenerator;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    /**
     * @param string $template
     * @param DocumentManagerInterface $documentManager
     * @param DocumentInspector $documentInspector
     * @param DocumentRegistry $documentRegistry
     * @param ChainRouteGeneratorInterface $chainRouteGenerator
     * @param ConflictResolverInterface $conflictResolver
     */
    public function __construct(
        $template,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        DocumentRegistry $documentRegistry,
        ChainRouteGeneratorInterface $chainRouteGenerator,
        ConflictResolverInterface $conflictResolver
    ) {
        parent::__construct('PageTreeRoute');

        $this->template = $template;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->documentRegistry = $documentRegistry;
        $this->chainRouteGenerator = $chainRouteGenerator;
        $this->conflictResolver = $conflictResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $propertyName = $property->getName();
        $value = [
            'page' => $this->readPage($propertyName, $node),
            'path' => $node->getPropertyValueWithDefault($propertyName, ''),
            'suffix' => $node->getPropertyValueWithDefault($propertyName . '-suffix', ''),
        ];

        $property->setValue($value);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();
        if (!$value) {
            return $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }

        $path = $this->getAttribute('path', $value, '');
        $page = $this->getAttribute('page', $value, ['uuid' => null, 'path' => '/', 'webspace' => $webspaceKey]);

        $suffix = $this->getAttribute('suffix', $value);
        if (!$suffix) {
            $suffix = $this->generateSuffix($node, $languageCode, $page['path']);
        }

        // generate url if not set
        if (!$path) {
            $path = rtrim($page['path'], '/') . '/' . $suffix;
        }

        $propertyName = $property->getName();
        $node->setProperty($propertyName, $path);
        $node->setProperty($propertyName . '-suffix', $suffix);

        $pagePropertyName = $propertyName . '-page';
        if ($node->hasProperty($pagePropertyName)) {
            $node->getProperty($pagePropertyName)->remove();
        }

        if (!$page['uuid']) {
            // no parent-page given

            return;
        }

        try {
            $document = $this->documentManager->find($page['uuid'], $languageCode);
            $node->setProperty($pagePropertyName, $page['uuid'], PropertyType::WEAKREFERENCE);
            $node->setProperty($pagePropertyName . '-path', $page['path']);
            $node->setProperty($pagePropertyName . '-webspace', $this->documentInspector->getWebspace($document));
        } catch (DocumentNotFoundException $exception) {
            // given document was not found
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $value = parent::getContentData($property);

        return $value['path'];
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        return $property->getValue();
    }

    /**
     * Read page-information from given node.
     *
     * @param string $propertyName
     * @param NodeInterface $node
     *
     * @return array
     */
    private function readPage($propertyName, NodeInterface $node)
    {
        $pagePropertyName = $propertyName . '-page';
        if (!$node->hasProperty($pagePropertyName)) {
            return;
        }

        try {
            $pageUuid = $node->getPropertyValue($pagePropertyName, PropertyType::STRING);
        } catch (ItemNotFoundException $exception) {
            return;
        }

        return [
            'uuid' => $pageUuid,
            'path' => $node->getPropertyValueWithDefault($pagePropertyName . '-path', ''),
            'webspace' => $node->getPropertyValueWithDefault($pagePropertyName . '-webspace', null),
        ];
    }

    /**
     * Returns value of array or default.
     *
     * @param string $name
     * @param array $value
     * @param mixed $default
     *
     * @return mixed
     */
    private function getAttribute($name, array $value, $default = null)
    {
        if (!array_key_exists($name, $value)) {
            return $default;
        }

        return $value[$name];
    }

    /**
     * Generate a new suffix for document.
     *
     * @param NodeInterface $node
     * @param string $locale
     * @param string $pagePath
     *
     * @return string
     */
    private function generateSuffix(NodeInterface $node, $locale, $pagePath)
    {
        $document = $this->documentRegistry->getDocumentForNode($node, $locale);
        $route = $this->chainRouteGenerator->generate($document);
        $route->setPath(rtrim($pagePath, '/') . '/' . ltrim($route->getPath(), '/'));

        $route = $this->conflictResolver->resolve($route);

        return substr($route->getPath(), strlen(rtrim($pagePath, '/')) + 1);
    }
}
