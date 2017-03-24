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

use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ArticlePageControllerTest extends SuluTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->initPhpcr();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    private function createArticle($title = 'Test-Article', $template = 'default_pages')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de',
            [
                'title' => $title,
                'pageTitle' => $title,
                'template' => $template,
                'authored' => '2016-01-01',
                'author' => $this->getTestUser()->getContact()->getId(),
            ]
        );

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function getArticle($uuid)
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles/' . $uuid . '?locale=de');

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function post($article, $pageTitle = 'Test-Page')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles/' . $article['id'] . '/pages?locale=de',
            [
                'pageTitle' => $pageTitle,
                'author' => $this->getTestUser()->getContact()->getId(),
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
        $this->assertEquals(2, $response['page']);

        $article = $this->getArticle($article['id']);
        $this->assertCount(1, $article['pages']);
        $this->assertEquals($response['id'], reset($article['pages'])['id']);
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
        $this->assertEquals($pageTitle, $response['pageTitle']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals(2, $response['page']);
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
                'author' => $this->getTestUser()->getContact()->getId(),
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($title, $response['title']);
        $this->assertEquals($pageTitle, $response['pageTitle']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals('Sulu is awesome', $response['article']);
        $this->assertEquals(2, $response['page']);
    }

    public function testDelete()
    {
        $article = $this->createArticle();
        $page = $this->post($article);

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/articles/' . $article['id'] . '/pages/' . $page['id']);

        $this->assertHttpStatusCode(204, $client->getResponse());

        $article = $this->getArticle($article['id']);
        $this->assertCount(0, $article['pages']);
    }
}
