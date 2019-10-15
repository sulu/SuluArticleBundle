<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\VirtualProxyInterface;
use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItem;
use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItemFactory;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Component\DocumentManager\DocumentManager;

class ArticleResourceItemFactoryTest extends TestCase
{
    public function testGetResourceItem()
    {
        $articleDocument = $this->getArticleDocument();
        $articleViewDocument = $this->getArticleViewDocument($articleDocument);
        $documentManager = $this->prophesize(DocumentManager::class);
        $proxyFactory = $this->prophesize(LazyLoadingValueHolderFactory::class);

        $articleResourceItemFactory = new ArticleResourceItemFactory(
            $documentManager->reveal(),
            $proxyFactory->reveal()
        );

        $proxyFactory->createProxy(Argument::cetera())
            ->willReturn(
                $this->prophesize(ArticleDocument::class)->willImplement(VirtualProxyInterface::class)->reveal()
            );

        $result = $articleResourceItemFactory->createResourceItem($articleViewDocument);

        $this->assertInstanceOf(VirtualProxyInterface::class, $result->getResource());
        $this->assertInstanceOf(ArticleDocument::class, $result->getResource());
        $this->assertInstanceOf(ArticleResourceItem::class, $result);
        $this->assertEquals($result->getUuid(), $articleDocument->getUuid());
    }

    /**
     * @return ArticleDocument
     */
    private function getArticleDocument()
    {
        $articleDocument = new ArticleDocument();
        $articleDocument->setUuid('123-123-123');
        $articleDocument->setLocale('de');

        return $articleDocument;
    }

    /**
     * @param ArticleDocument $articleDocument
     *
     * @return ArticleViewDocument
     */
    private function getArticleViewDocument(ArticleDocument $articleDocument)
    {
        $articleViewDocument = new ArticleViewDocument($articleDocument->getUuid());
        $articleViewDocument->setLocale($articleDocument->getLocale());

        return $articleViewDocument;
    }
}
