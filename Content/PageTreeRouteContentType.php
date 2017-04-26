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

use Ferrandini\Urlizer;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * Provides page_tree_route content-type.
 */
class PageTreeRouteContentType extends SimpleContentType
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @param string $template
     * @param PropertyEncoder $propertyEncoder
     */
    public function __construct($template, PropertyEncoder $propertyEncoder)
    {
        parent::__construct('PageTreeRoute');

        $this->template = $template;
        $this->propertyEncoder = $propertyEncoder;
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

        $titlePropertyName = $this->propertyEncoder->localizedContentName('title', $languageCode);
        $title = $node->getPropertyValueWithDefault($titlePropertyName, null);

        $path = $this->getAttribute('path', $value, '');
        $page = $this->getAttribute('page', $value, ['uuid' => null, 'path' => '/']);
        $suffix = $this->getAttribute('suffix', $value, Urlizer::urlize($title));

        // generate url if not set
        if (!$path) {
            $path = rtrim($page['path'], '/') . '/' . $suffix;
        }

        $propertyName = $property->getName();
        $node->setProperty($propertyName, $path);
        $node->setProperty($propertyName . '-suffix', $suffix);
        $node->setProperty($propertyName . '-page', $page['uuid'], PropertyType::WEAKREFERENCE);
        $node->setProperty($propertyName . '-page-path', $page['path']);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
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

        return [
            'uuid' => $node->getPropertyValue($pagePropertyName, PropertyType::STRING),
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
}
