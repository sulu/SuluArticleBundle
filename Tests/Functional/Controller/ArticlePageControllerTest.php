<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Ferrandini\Urlizer;
use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ArticlePageControllerTest extends SuluTestCase
{
    use ArticleViewDocumentIdTrait;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->purgeIndex();

        $this->purgeDatabase();
        $this->initPhpcr();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    private function createArticle($title = 'Test-Article', $template = 'default_pages', $locale = 'de')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=' . $locale,
            [
                'title' => $title,
                'pageTitle' => $title,
                'template' => $template,
                'authored' => '2016-01-01',
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function createArticleLocale($article, $title = 'Test-Article', $template = 'default_pages', $locale = 'en')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=' . $locale,
            [
                'title' => $title,
                'pageTitle' => $title,
                'template' => $template,
                'authored' => '2016-01-01',
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function getArticle($uuid, $locale = 'de')
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles/' . $uuid . '?locale=' . $locale);

        $this->assertHttpStatusCode(200, $client->getResponse());

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function post($article, $pageTitle = 'Test-Page', $template = 'default_pages', $locale = 'de')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles/' . $article['id'] . '/pages?locale=' . $locale,
            [
                'pageTitle' => $pageTitle,
                'template' => $template,
            ]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        return json_decode($client->getResponse()->getContent(), true);
    }

    public function testPost($title = 'Test-Article', $pageTitle = 'Test-Page', $template = 'default_pages')
    {
        $article = $this->createArticle($title, $template);
        $response = $this->post($article, $pageTitle);

        $this->assertEquals($title, $response['title']);
        $this->assertEquals($pageTitle, $response['pageTitle']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals($this->getRoute($title, 2), $response['route']);
        $this->assertEquals(2, $response['pageNumber']);

        $this->assertEquals($article['id'], $response['_embedded']['article']['id']);

        $article = $this->getArticle($article['id']);
        $this->assertCount(1, $article['_embedded']['pages']);
        $this->assertEquals($response['id'], reset($article['_embedded']['pages'])['id']);

        $articleViewDocument = $this->findViewDocument($article['id'], 'de');
        $this->assertCount(1, $articleViewDocument->getPages());
        $this->assertEquals(2, $articleViewDocument->getPages()[0]->pageNumber);
        $this->assertEquals($pageTitle, $articleViewDocument->getPages()[0]->title);
        $this->assertEquals($response['id'], $articleViewDocument->getPages()[0]->uuid);
    }

    public function testPostMultiplePages($title = 'Test-Article')
    {
        $article = $this->createArticle($title);

        $response1 = $this->post($article, 'Test-1');
        $this->assertEquals($title, $response1['title']);
        $this->assertEquals('Test-1', $response1['pageTitle']);
        $this->assertEquals($this->getRoute($title, 2), $response1['route']);
        $this->assertEquals(2, $response1['pageNumber']);

        $response2 = $this->post($article, 'Test-2');
        $this->assertEquals($title, $response2['title']);
        $this->assertEquals('Test-2', $response2['pageTitle']);
        $this->assertEquals($this->getRoute($title, 3), $response2['route']);
        $this->assertEquals(3, $response2['pageNumber']);

        $article = $this->getArticle($article['id']);
        $this->assertCount(2, $article['_embedded']['pages']);
        $this->assertEquals($response1['id'], $article['_embedded']['pages'][0]['id']);
        $this->assertEquals($response2['id'], $article['_embedded']['pages'][1]['id']);

        $articleViewDocument = $this->findViewDocument($article['id'], 'de');
        $this->assertCount(2, $articleViewDocument->getPages());

        $this->assertEquals(2, $articleViewDocument->getPages()[0]->pageNumber);
        $this->assertEquals('Test-1', $articleViewDocument->getPages()[0]->title);
        $this->assertEquals($response1['id'], $articleViewDocument->getPages()[0]->uuid);
        $this->assertEquals($response1['route'], $articleViewDocument->getPages()[0]->routePath);

        $this->assertEquals(3, $articleViewDocument->getPages()[1]->pageNumber);
        $this->assertEquals('Test-2', $articleViewDocument->getPages()[1]->title);
        $this->assertEquals($response2['id'], $articleViewDocument->getPages()[1]->uuid);
        $this->assertEquals($response2['route'], $articleViewDocument->getPages()[1]->routePath);
    }

    public function testGet($title = 'Test-Article', $pageTitle = 'Test-Page', $template = 'default_pages')
    {
        $article = $this->createArticle($title, $template);
        $page = $this->post($article, $pageTitle);

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles/' . $article['id'] . '/pages/' . $page['id'] . '?locale=de');

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($title, $response['title']);
        $this->assertEquals($this->getRoute($title, 2), $response['route']);
        $this->assertEquals($pageTitle, $response['pageTitle']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals(2, $response['pageNumber']);
    }

    public function testPut($title = 'Test-Article', $pageTitle = 'New-Page-Title', $template = 'default_pages')
    {
        $article = $this->createArticle($title, $template);
        $page = $this->post($article);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '/pages/' . $page['id'] . '?locale=de',
            [
                'pageTitle' => $pageTitle,
                'article' => 'Sulu is awesome',
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($title, $response['title']);
        $this->assertEquals($this->getRoute($title, 2), $response['route']);
        $this->assertEquals($pageTitle, $response['pageTitle']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals('Sulu is awesome', $response['article']);
        $this->assertEquals(2, $response['pageNumber']);

        $articleViewDocument = $this->findViewDocument($article['id'], 'de');
        $this->assertCount(1, $articleViewDocument->getPages());
        $this->assertEquals(2, $articleViewDocument->getPages()[0]->pageNumber);
        $this->assertEquals($pageTitle, $articleViewDocument->getPages()[0]->title);
        $this->assertEquals($response['id'], $articleViewDocument->getPages()[0]->uuid);
    }

    public function testPutPublish($title = 'Test-Article', $pageTitle = 'New-Page-Title', $template = 'default_pages')
    {
        $article = $this->createArticle($title, $template);
        $page = $this->post($article);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '/pages/' . $page['id'] . '?locale=de&action=publish',
            [
                'pageTitle' => $pageTitle,
                'article' => 'Sulu is awesome',
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($title, $response['title']);
        $this->assertEquals($this->getRoute($title, 2), $response['route']);
        $this->assertEquals($pageTitle, $response['pageTitle']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals('Sulu is awesome', $response['article']);
        $this->assertEquals(2, $response['pageNumber']);

        $articleViewDocument = $this->findViewDocument($article['id'], 'de');
        $this->assertCount(1, $articleViewDocument->getPages());
        $this->assertEquals(2, $articleViewDocument->getPages()[0]->pageNumber);
        $this->assertEquals($pageTitle, $articleViewDocument->getPages()[0]->title);
        $this->assertEquals($response['id'], $articleViewDocument->getPages()[0]->uuid);
    }

    public function testDelete()
    {
        $article = $this->createArticle();
        $page = $this->post($article);

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/articles/' . $article['id'] . '/pages/' . $page['id'] . '?locale=de');

        $this->assertHttpStatusCode(204, $client->getResponse());

        $article = $this->getArticle($article['id']);
        $this->assertCount(0, $article['_embedded']['pages']);

        $articleViewDocument = $this->findViewDocument($article['id'], 'de');
        $this->assertCount(0, $articleViewDocument->getPages());
    }

    public function testHandleGhostArticlePageAndArticle($pageTitle = 'Sulu is awesome')
    {
        $article = $this->createArticle();
        $page = $this->post($article);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '/pages/' . $page['id'] . '?locale=en',
            [
                'pageTitle' => $pageTitle,
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertArrayNotHasKey('type', $response);
        $this->assertEquals($pageTitle, $response['pageTitle']);

        // article should stay ghost
        $article = $this->getArticle($article['id'], 'en');
        $this->assertEquals('ghost', $article['type']['name']);
    }

    public function testHandleGhostArticlePage($pageTitle = 'Sulu is awesome')
    {
        $article = $this->createArticle();
        $page = $this->post($article);

        $article = $this->createArticleLocale($article, 'XXX');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '/pages/' . $page['id'] . '?locale=en',
            [
                'pageTitle' => $pageTitle,
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertArrayNotHasKey('type', $response);
        $this->assertEquals($pageTitle, $response['pageTitle']);
    }

    private function purgeIndex()
    {
        /** @var IndexerInterface $indexer */
        $indexer = $this->getContainer()->get('sulu_article.elastic_search.article_indexer');
        $indexer->clear();
    }

    /**
     * @param $uuid
     * @param $locale
     *
     * @return ArticleViewDocumentInterface
     */
    private function findViewDocument($uuid, $locale)
    {
        /** @var Manager $manager */
        $manager = $this->getContainer()->get('es.manager.default');

        return $manager->find(ArticleViewDocument::class, $this->getViewDocumentId($uuid, $locale));
    }

    private function getRoute($title, $page)
    {
        return '/articles/' . Urlizer::urlize($title) . '/page-' . $page;
    }
}
