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

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * Creates article resource items for given article view document.
 */
class ArticleResourceItemFactory
{
    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    protected $proxyFactory;

    /**
     * @param DocumentManagerInterface $documentManager
     * @param LazyLoadingValueHolderFactory $proxyFactory
     */
    public function __construct(
        DocumentManagerInterface $documentManager,
        LazyLoadingValueHolderFactory $proxyFactory
    ) {
        $this->documentManager = $documentManager;
        $this->proxyFactory = $proxyFactory;
    }

    /**
     * Creates and returns article source item with proxy document.
     *
     * @param ArticleViewDocumentInterface $articleViewDocument
     *
     * @return ArticleResourceItem
     */
    public function createResourceItem(ArticleViewDocumentInterface $articleViewDocument)
    {
        return new ArticleResourceItem(
            $articleViewDocument,
            $this->getResource($articleViewDocument->getUuid(), $articleViewDocument->getLocale())
        );
    }

    /**
     * Returns Proxy document for uuid.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return object
     */
    private function getResource($uuid, $locale)
    {
        return $this->proxyFactory->createProxy(
            ArticleDocument::class,
            function (
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($uuid, $locale) {
                $initializer = null;
                $wrappedObject = $this->documentManager->find($uuid, $locale);

                return true;
            }
        );
    }
}
