<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Structure;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;

/**
 * Factory for content-proxies.
 */
class ContentProxyFactory
{
    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param LazyLoadingValueHolderFactory $proxyFactory
     */
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        LazyLoadingValueHolderFactory $proxyFactory
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->proxyFactory = $proxyFactory;
    }

    /**
     * Create content-proxy for given structure.
     *
     * @param StructureInterface $structure
     * @param array $data
     *
     * @return array
     */
    public function createContentProxy(StructureInterface $structure, array $data)
    {
        return $this->proxyFactory->createProxy(
            \ArrayObject::class,
            function (
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($structure, $data) {
                $initializer = null;
                $wrappedObject = new \ArrayObject($this->resolveContent($structure, $data));

                return true;
            }
        );
    }

    /**
     * Resolve content from given data with the structure.
     *
     * @param StructureInterface $structure
     * @param array $data
     *
     * @return array
     */
    private function resolveContent(StructureInterface $structure, array $data)
    {
        $content = [];
        foreach ($structure->getProperties(true) as $child) {
            if (array_key_exists($child->getName(), $data)) {
                $child->setValue($data[$child->getName()]);
            }

            $contentType = $this->contentTypeManager->get($child->getContentTypeName());
            $content[$child->getName()] = $contentType->getContentData($child);
        }

        return $content;
    }

    /**
     * Create view-proxy for given structure.
     *
     * @param StructureInterface $structure
     * @param array $data
     *
     * @return array
     */
    public function createViewProxy(StructureInterface $structure, array $data)
    {
        return $this->proxyFactory->createProxy(
            \ArrayObject::class,
            function (
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($structure, $data) {
                $initializer = null;
                $wrappedObject = new \ArrayObject($this->resolveView($structure, $data));

                return true;
            }
        );
    }

    /**
     * Resolve view from given data with the structure.
     *
     * @param StructureInterface $structure
     * @param array $data
     *
     * @return array
     */
    private function resolveView(StructureInterface $structure, array $data)
    {
        $view = [];
        foreach ($structure->getProperties(true) as $child) {
            if (array_key_exists($child->getName(), $data)) {
                $child->setValue($data[$child->getName()]);
            }

            $contentType = $this->contentTypeManager->get($child->getContentTypeName());
            $view[$child->getName()] = $contentType->getViewData($child);
        }

        return $view;
    }
}
