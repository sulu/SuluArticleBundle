<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Content;

use ONGR\ElasticsearchBundle\Service\Manager;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ArticleBundle\Content\PageTreeArticleDataProvider;
use Sulu\Bundle\DocumentManagerBundle\Slugifier\Urlizer;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\DatasourceItem;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class PageTreeArticleDataProviderTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();

        $this->initPhpcr();

        /** @var Manager $manager */
        $manager = $this->getContainer()->get('es.manager.default');
        $manager->dropAndCreateIndex();

        /** @var Manager $manager */
        $manager = $this->getContainer()->get('es.manager.live');
        $manager->dropAndCreateIndex();
    }

    public function testFilterByDataSource()
    {
        $page1 = $this->createPage('Test Page', '/page-1');
        $page2 = $this->createPage('Test Page', '/page-2');

        $articles = [
            $this->createArticle($page1, 'Test 1'),
            $this->createArticle($page2, 'Test 2'),
        ];

        $dataProvider = $this->getContainer()->get('sulu_article.content.page_tree_data_provider');
        $result = $dataProvider->resolveDataItems(['dataSource' => $page1->getUuid()], [], ['locale' => 'de']);

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertCount(1, $result->getItems());
        $this->assertEquals($articles[0]['id'], $result->getItems()[0]->getId());
    }

    public function testSortByTitle()
    {
        $page1 = $this->createPage('Test Page', '/page-1');

        $articles = [
            $this->createArticle($page1, 'Test A'),
            $this->createArticle($page1, 'Test B'),
        ];

        $filters = [
            'dataSource' => $page1->getUuid(),
            'sortBy' => 'title.raw',
            'sortMethod' => 'asc',
        ];

        /** @var PageTreeArticleDataProvider $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.page_tree_data_provider');
        $result = $dataProvider->resolveDataItems($filters, [], ['locale' => 'de']);

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals($articles[0]['title'], $result->getItems()[0]->getTitle());
        $this->assertEquals($articles[1]['title'], $result->getItems()[1]->getTitle());

        $filters['sortMethod'] = 'desc';

        $result = $dataProvider->resolveDataItems($filters, [], ['locale' => 'de']);
        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals($articles[0]['title'], $result->getItems()[1]->getTitle());
        $this->assertEquals($articles[1]['title'], $result->getItems()[0]->getTitle());
    }

    public function testResolveDataSource()
    {
        $page = $this->createPage('Test Page', '/page');

        $dataProvider = $this->getContainer()->get('sulu_article.content.page_tree_data_provider');
        $result = $dataProvider->resolveDatasource($page->getUuid(), [], ['locale' => 'de']);

        $this->assertInstanceOf(DatasourceItem::class, $result);
        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals($page->getTitle(), $result->getTitle());
    }

    public function testResolveDataItemsUnpublished()
    {
        $page = $this->createPage('Test Page', '/page', 'de', false);

        $articles = [
            $this->createArticle($page, 'Test 1'),
            $this->createArticle($page, 'Test 2'),
        ];

        $this->ensureKernelShutdown();
        self::bootKernel(['sulu.context' => SuluKernel::CONTEXT_WEBSITE]);

        $dataProvider = $this->getContainer()->get('sulu_article.content.page_tree_data_provider');
        $result = $dataProvider->resolveDataItems(['dataSource' => $page->getUuid()], [], ['locale' => 'de']);

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertCount(2, $result->getItems());
    }

    public function testResolveDataSourceUnpublished()
    {
        $page = $this->createPage('Test Page', '/page', 'de', false);

        $this->ensureKernelShutdown();
        self::bootKernel(['sulu.context' => SuluKernel::CONTEXT_WEBSITE]);

        $dataProvider = $this->getContainer()->get('sulu_article.content.page_tree_data_provider');
        $result = $dataProvider->resolveDatasource($page->getUuid(), [], ['locale' => 'de']);

        $this->assertNull($result);
    }

    public function testResolveDataSourceNull()
    {
        $dataProvider = $this->getContainer()->get('sulu_article.content.page_tree_data_provider');

        $this->assertNull($dataProvider->resolveDatasource(null, [], ['locale' => 'de']));
    }

    private function createArticle(PageDocument $page, $title = 'Test-Article', $template = 'page_tree_route')
    {
        $this->client->jsonRequest(
            'POST',
            '/api/articles?locale=de&action=publish',
            [
                'title' => $title,
                'template' => $template,
                'routePath' => [
                    'page' => [
                        'uuid' => $page->getUuid(),
                        'path' => $page->getResourceSegment(),
                    ],
                    'suffix' => Urlizer::urlize($title),
                    'path' => $page->getResourceSegment() . '/' . Urlizer::urlize($title),
                ],
            ]
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Create a new page.
     *
     * @param string $title
     * @param string $resourceSegment
     * @param string $locale
     * @param bool $published
     *
     * @return PageDocument
     */
    private function createPage($title, $resourceSegment, $locale = 'de', $published = true)
    {
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $sessionManager = $this->getContainer()->get('sulu.phpcr.session');

        $page = $documentManager->create('page');

        $uuidReflection = new \ReflectionProperty(PageDocument::class, 'uuid');
        $uuidReflection->setAccessible(true);
        $uuidReflection->setValue($page, Uuid::uuid4()->toString());

        $page->setTitle($title);
        $page->setStructureType('default');
        $page->setParent($documentManager->find($sessionManager->getContentPath('sulu_io')));
        $page->setResourceSegment($resourceSegment);

        $documentManager->persist($page, $locale);

        if ($published) {
            $documentManager->publish($page, $locale);
        }

        $documentManager->flush();

        return $page;
    }
}
