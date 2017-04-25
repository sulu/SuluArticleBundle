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

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
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
     * @param string $template
     */
    public function __construct($template)
    {
        parent::__construct('PageTreeRoute');

        $this->template = $template;
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

        $propertyName = $property->getName();
        $node->setProperty($propertyName, $value['path']);
        $node->setProperty($propertyName . '-suffix', $value['suffix']);
        $node->setProperty($propertyName . '-page', $value['page']['uuid'], PropertyType::WEAKREFERENCE);
        $node->setProperty($propertyName . '-page-path', $value['page']['path']);
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
}
