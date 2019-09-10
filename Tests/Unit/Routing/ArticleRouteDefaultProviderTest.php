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

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Resolver\WebspaceResolver;
use Sulu\Bundle\ArticleBundle\Document\Structure\ArticleBridge;
use Sulu\Bundle\ArticleBundle\Routing\ArticleRouteDefaultProvider;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Document\UnknownDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Webspace;

class ArticleRouteDefaultProviderTest extends TestCase
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
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ArticleRouteDefaultProvider
     */
    private $provider;

    /**
     * @var WebspaceResolver
     */
    private $webspaceResolver;

    /**
     * @var RequestAnalyzer
     */
    private $requestAnalyzer;

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
        $this->cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolverInterface::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->webspaceResolver = $this->prophesize(WebspaceResolver::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzer::class);

        $this->provider = new ArticleRouteDefaultProvider(
            $this->documentManager->reveal(),
            $this->structureMetadataFactory->reveal(),
            $this->cacheLifetimeResolver->reveal(),
            $this->structureManager->reveal(),
            $this->webspaceResolver->reveal(),
            $this->requestAnalyzer->reveal()
        );
    }

    public function publishedDataProvider()
    {
        $articleDocument = new ArticleDocument();
        $articleDocument->setWorkflowStage(WorkflowStage::TEST);

        $articleDocumentPublished = new ArticleDocument();
        $articleDocumentPublished->setWorkflowStage(WorkflowStage::PUBLISHED);

        $unknownDocument = new UnknownDocument();

        return [
            [$articleDocument, 'test', 'test', [], false],
            [$articleDocumentPublished, 'test', 'test', [], true],
            [$articleDocumentPublished, 'test', 'other_webspace', ['test', 'one_more_other_webspace'], true],
            [$unknownDocument, 'test', null, [], false],
        ];
    }

    /**
     * @dataProvider publishedDataProvider
     */
    public function testIsPublished(
        $document,
        $webspaceKey,
        $documentMainWebspace,
        $documentAdditionalWebspaces,
        $result
    ) {
        if ($document instanceof ArticleDocument) {
            $this->webspaceResolver->resolveMainWebspace($document)->willReturn($documentMainWebspace);
            $this->webspaceResolver->resolveAdditionalWebspaces($document)->willReturn($documentAdditionalWebspaces);
        }

        $this->documentManager->find($this->entityId, $this->locale)->willReturn($document);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn($webspaceKey);

        $this->requestAnalyzer->getWebspace()->willReturn($webspace->reveal());

        $this->assertEquals($result, $this->provider->isPublished($this->entityClass, $this->entityId, $this->locale));
    }

    public function testGetByEntity()
    {
        $article = $this->prophesize(ArticleDocument::class);
        $article->getStructureType()->willReturn('default');
        $article->getPageNumber()->willReturn(1);

        $structureMetadata = new StructureMetadata('default');
        $structureMetadata->setView('default.html.twig');
        $structureMetadata->setCacheLifetime(['type' => 'seconds', 'value' => 3600]);
        $structureMetadata->setController('SuluArticleBundle:Default:index');

        $this->documentManager->find($this->entityId, $this->locale)->willReturn($article->reveal());
        $this->structureMetadataFactory->getStructureMetadata('article', 'default')->willReturn($structureMetadata);
        $this->cacheLifetimeResolver->supports('seconds', 3600)->willReturn(true);
        $this->cacheLifetimeResolver->resolve('seconds', 3600)->willReturn(3600);

        $structure = $this->prophesize(ArticleBridge::class);
        $structure->setDocument($article->reveal())->shouldBeCalled();
        $this->structureManager->wrapStructure('article', $structureMetadata)->willReturn($structure->reveal());

        $result = $this->provider->getByEntity($this->entityClass, $this->entityId, $this->locale);

        $this->assertEquals(
            [
                'object' => $article->reveal(),
                'structure' => $structure->reveal(),
                'view' => 'default.html.twig',
                'pageNumber' => 1,
                '_controller' => 'SuluArticleBundle:Default:index',
                '_cacheLifetime' => 3600,
            ],
            $result
        );
    }

    public function testGetByEntityArticlePage()
    {
        $articlePage = $this->prophesize(ArticlePageDocument::class);
        $articlePage->getPageNumber()->willReturn(2);

        $article = $this->prophesize(ArticleDocument::class);
        $article->getStructureType()->willReturn('default');
        $article->getPageNumber()->willReturn(1);
        $articlePage->getParent()->willReturn($article->reveal());

        $structureMetadata = new StructureMetadata('default');
        $structureMetadata->setView('default.html.twig');
        $structureMetadata->setCacheLifetime(['type' => 'seconds', 'value' => 3600]);
        $structureMetadata->setController('SuluArticleBundle:Default:index');

        $this->documentManager->find($this->entityId, $this->locale)->willReturn($articlePage->reveal());
        $this->structureMetadataFactory->getStructureMetadata('article', 'default')->willReturn($structureMetadata);
        $this->cacheLifetimeResolver->supports('seconds', 3600)->willReturn(true);
        $this->cacheLifetimeResolver->resolve('seconds', 3600)->willReturn(3600);

        $structure = $this->prophesize(ArticleBridge::class);
        $structure->setDocument($article->reveal())->shouldBeCalled();
        $this->structureManager->wrapStructure('article', $structureMetadata)->willReturn($structure->reveal());

        $result = $this->provider->getByEntity($this->entityClass, $this->entityId, $this->locale);

        $this->assertEquals(
            [
                'object' => $article->reveal(),
                'structure' => $structure->reveal(),
                'view' => 'default.html.twig',
                'pageNumber' => 2,
                '_controller' => 'SuluArticleBundle:Default:index',
                '_cacheLifetime' => 3600,
            ],
            $result
        );
    }
}
