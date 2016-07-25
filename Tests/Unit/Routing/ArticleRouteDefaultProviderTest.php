<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Routing;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Routing\ArticleRouteDefaultProvider;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Document\UnknownDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class ArticleRouteDefaultProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var ArticleRouteDefaultProvider
     */
    private $provider;

    /**
     * @var string
     */
    private $entityClass = ArticleDocument::class;

    /**
     * @var string
     */
    private $entityId = '123-123-123';

    /**
     * @var string
     */
    private $locale = 'de';

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);

        $this->provider = new ArticleRouteDefaultProvider(
            $this->documentManager->reveal(),
            $this->structureMetadataFactory->reveal()
        );
    }

    public function publishedDataProvider()
    {
        return [
            [new UnknownDocument(), false],
            [new ArticleDocument(), true],
        ];
    }

    /**
     * @dataProvider publishedDataProvider
     */
    public function testIsPublished($document, $result)
    {
        $this->documentManager->find($this->entityId, $this->locale)->willReturn($document);

        $this->assertEquals($result, $this->provider->isPublished($this->entityClass, $this->entityId, $this->locale));
    }

    public function testGetByEntity()
    {
        $article = $this->prophesize(ArticleDocument::class);
        $article->getStructureType()->willReturn('default');

        $structureMetadata = new StructureMetadata('default');
        $structureMetadata->view = 'default.html.twig';
        $structureMetadata->cacheLifetime = 3600;
        $structureMetadata->controller = 'SuluArticleBundle:Default:index';

        $this->documentManager->find($this->entityId, $this->locale)->willReturn($article->reveal());
        $this->structureMetadataFactory->getStructureMetadata('article', 'default')->willReturn($structureMetadata);

        $result = $this->provider->getByEntity($this->entityClass, $this->entityId, $this->locale);

        $this->assertEquals(
            [
                'object' => $article->reveal(),
                'view' => 'default.html.twig',
                '_controller' => 'SuluArticleBundle:Default:index',
                '_cacheLifetime' => 3600,
            ],
            $result
        );
    }
}
