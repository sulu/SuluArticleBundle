<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Content;

use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Component\DocumentManager\DocumentRegistry;

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
     * @param DocumentRegistry $documentRegistry
     * @param ChainRouteGeneratorInterface $chainRouteGenerator
     * @param ConflictResolverInterface $conflictResolver
     */
    public function __construct(
        $template,
        DocumentRegistry $documentRegistry,
        ChainRouteGeneratorInterface $chainRouteGenerator,
        ConflictResolverInterface $conflictResolver
    ) {
        parent::__construct('PageTreeRoute');

        $this->template = $template;
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
        $page = $this->getAttribute('page', $value, ['uuid' => null, 'path' => '/']);

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
        $node->setProperty($pagePropertyName, $page['uuid'], PropertyType::WEAKREFERENCE);
        $node->setProperty($pagePropertyName . '-path', $page['path']);
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
        $route->setPath($pagePath . '/' . ltrim($route->getPath(), '/'));

        $route = $this->conflictResolver->resolve($route);

        return substr($route->getPath(), strlen($pagePath) + 1);
    }
}
