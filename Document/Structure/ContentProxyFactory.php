<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Structure;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use ProxyManager\Proxy\VirtualProxyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\RequestStack;

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
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        LazyLoadingValueHolderFactory $proxyFactory,
        RequestStack $requestStack
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->proxyFactory = $proxyFactory;
        $this->requestStack = $requestStack;
    }

    /**
     * Create content-proxy for given structure.
     *
     * @return VirtualProxyInterface
     */
    public function createContentProxy(StructureInterface $structure, array $data)
    {
        return $this->proxyFactory->createProxy(
            \ArrayObject::class,
            function(
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
     */
    private function resolveContent(StructureInterface $structure, array $data): array
    {
        $structure->setWebspaceKey($this->getWebspaceKey());

        $content = [];
        foreach ($structure->getProperties(true) as $child) {
            if (\array_key_exists($child->getName(), $data)) {
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
     * @return VirtualProxyInterface
     */
    public function createViewProxy(StructureInterface $structure, array $data)
    {
        return $this->proxyFactory->createProxy(
            \ArrayObject::class,
            function(
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
     */
    private function resolveView(StructureInterface $structure, array $data): array
    {
        $structure->setWebspaceKey($this->getWebspaceKey());

        $view = [];
        foreach ($structure->getProperties(true) as $child) {
            if (\array_key_exists($child->getName(), $data)) {
                $child->setValue($data[$child->getName()]);
            }

            $contentType = $this->contentTypeManager->get($child->getContentTypeName());
            $view[$child->getName()] = $contentType->getViewData($child);
        }

        return $view;
    }

    private function getWebspaceKey(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        /** @var RequestAttributes $attributes */
        $attributes = $request->attributes->get('_sulu');
        if (!$attributes) {
            return null;
        }

        return $attributes->getAttribute('webspaceKey') ?? $attributes->getAttribute('webspace')->getKey();
    }
}
