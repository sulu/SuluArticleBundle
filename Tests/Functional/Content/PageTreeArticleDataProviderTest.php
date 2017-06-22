<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Content;

use Ferrandini\Urlizer;
use ONGR\ElasticsearchBundle\Service\Manager;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\DatasourceItem;

class PageTreeArticleDataProviderTest extends SuluTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

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

    public function testResolveDataSource()
    {
        $page = $this->createPage('Test Page', '/page');

        $dataProvider = $this->getContainer()->get('sulu_article.content.page_tree_data_provider');
        $result = $dataProvider->resolveDatasource($page->getUuid(), [], ['locale' => 'de']);

        $this->assertInstanceOf(DatasourceItem::class, $result);
        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals($page->getTitle(), $result->getTitle());
    }

    public function testResolveDataSourceNull()
    {
        $dataProvider = $this->getContainer()->get('sulu_article.content.page_tree_data_provider');

        $this->assertNull($dataProvider->resolveDatasource(null, [], ['locale' => 'de']));
    }

    private function createArticle(PageDocument $page, $title = 'Test-Article', $template = 'page_tree_route')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
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

        return json_decode($client->getResponse()->getContent(), true);
    }

    /**
     * Create a new page.
     *
     * @param string $title
     * @param string $resourceSegment
     * @param string $locale
     *
     * @return PageDocument
     */
    private function createPage($title, $resourceSegment, $locale = 'de')
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
        $documentManager->publish($page, $locale);
        $documentManager->flush();

        return $page;
    }
}
