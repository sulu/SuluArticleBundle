<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\PageTree;

use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ArticleBundle\PageTree\PageTreeRepository;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\BrowserKit\Client;

class PageTreeRepositoryTest extends SuluTestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var PageTreeRepository
     */
    private $pageTreeRepository;

    /**
     * @var string
     */
    private $locale = 'en';

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->initPhpcr();
        $this->purgeDatabase();

        $this->client = $this->createAuthenticatedClient();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->pageTreeRepository  = $this->getContainer()->get('sulu_article.page_tree_route.updater.request');
    }

    public function testUpdate()
    {
        $pages = [
            $this->createPage('Test 1', '/test-1'),
            $this->createPage('Test 2', '/test-2'),
        ];

        $articles = [
            $this->createArticle($this->getPathData($pages[0], 'article-1')),
            $this->createArticle($this->getPathData($pages[0], 'article-2')),
            $this->createArticle($this->getPathData($pages[1], 'article-3')),
        ];
        $this->documentManager->flush();

        $pages[0]->setResourceSegment('/test-3');
        $this->documentManager->persist($pages[0], $this->locale);
        $this->documentManager->publish($pages[0], $this->locale);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $this->pageTreeRepository->update($pages[0]);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $result = [
            $this->documentManager->find($articles[0]['id'], $this->locale),
            $this->documentManager->find($articles[1]['id'], $this->locale),
            $this->documentManager->find($articles[2]['id'], $this->locale),
        ];

        $this->assertEquals('/test-3/article-1', $result[0]->getRoutePath());
        $this->assertEquals('/test-3/article-2', $result[1]->getRoutePath());
        $this->assertEquals('/test-2/article-3', $result[2]->getRoutePath());
    }

    public function testMove()
    {
        $pages = [
            $this->createPage('Test 1', '/test-1'),
            $this->createPage('Test 2', '/test-2'),
            $this->createPage('Test 3', '/test-3'),
        ];

        $articles = [
            $this->createArticle($this->getPathData($pages[0], 'article-1')),
            $this->createArticle($this->getPathData($pages[0], 'article-2')),
            $this->createArticle($this->getPathData($pages[1], 'article-3')),
        ];

        $this->documentManager->flush();
        $this->documentManager->clear();

        $this->pageTreeRepository->move('/test-1', $pages[2]);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $result = [
            $this->documentManager->find($articles[0]['id'], $this->locale),
            $this->documentManager->find($articles[1]['id'], $this->locale),
            $this->documentManager->find($articles[2]['id'], $this->locale),
        ];

        $this->assertEquals('/test-3/article-1', $result[0]->getRoutePath());
        $this->assertEquals('/test-3/article-2', $result[1]->getRoutePath());
        $this->assertEquals('/test-2/article-3', $result[2]->getRoutePath());
    }

    public function testMoveArticlePage()
    {
        $pages = [
            $this->createPage('Test 1', '/test-1'),
            $this->createPage('Test 2', '/test-2'),
        ];

        $article = $this->createArticle($this->getPathData($pages[0], 'article-1'));

        $articlePages = [
            $this->createArticlePage($article, 'Test page 1'),
            $this->createArticlePage($article, 'Test page 2'),
            $this->createArticlePage($article, 'Test page 3'),
        ];

        $this->documentManager->flush();
        $this->documentManager->clear();

        $this->pageTreeRepository->move('/test-1', $pages[1]);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $result = $this->documentManager->find($article['id'], $this->locale);
        $this->assertEquals('/test-2/article-1', $result->getRoutePath());

        $children = array_values(iterator_to_array($result->getChildren()));

        $this->assertEquals('/test-2/article-1/page-2', $children[0]->getRoutePath());
        $this->assertEquals('/test-2/article-1/page-3', $children[1]->getRoutePath());
        $this->assertEquals('/test-2/article-1/page-4', $children[2]->getRoutePath());
    }

    /**
     * Returns property value for given page.
     *
     * @param PageDocument $page
     * @param string $suffix
     *
     * @return array
     */
    private function getPathData(PageDocument $page, $suffix)
    {
        return [
            'page' => [
                'uuid' => $page->getUuid(),
                'path' => $page->getResourceSegment(),
            ],
            'suffix' => $suffix,
            'path' => $page->getResourceSegment() . '/' . $suffix,
        ];
    }

    /**
     * Create a new article.
     *
     * @param array $routePathData
     * @param string $title
     *
     * @return array
     */
    private function createArticle(array $routePathData, $title = 'Test Article')
    {
        $this->client->request(
            'POST',
            '/api/articles?locale=' . $this->locale,
            [
                'title' => $title,
                'template' => 'page_tree_route',
                'routePath' => $routePathData,
                'authored' => date('c', strtotime('2016-01-01')),
            ]
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Create article page.
     *
     * @param array $article
     * @param string $pageTitle
     * @param string $template
     * @param string $locale
     *
     * @return array
     */
    private function createArticlePage(
        array $article,
        $pageTitle = 'Test-Page',
        $template = 'default_pages',
        $locale = 'de'
    ) {
        if (!$article || !array_key_exists('id', $article)) {
            throw new \Exception('Article array needs an ID!');
        }

        $this->client->request(
            'POST',
            '/api/articles/' . $article['id'] . '/pages?locale=' . $locale,
            [
                'pageTitle' => $pageTitle,
                'template' => $template,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Create a new page.
     *
     * @param string $title
     * @param string $resourceSegment
     *
     * @return PageDocument
     */
    private function createPage($title = 'Test Page', $resourceSegment = '/test-page')
    {
        $sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $page = $this->documentManager->create('page');

        $uuidReflection = new \ReflectionProperty(PageDocument::class, 'uuid');
        $uuidReflection->setAccessible(true);
        $uuidReflection->setValue($page, Uuid::uuid4()->toString());

        $page->setTitle($title);
        $page->setStructureType('default');
        $page->setParent($this->documentManager->find($sessionManager->getContentPath('sulu_io')));
        $page->setResourceSegment($resourceSegment);

        $this->documentManager->persist($page, $this->locale);
        $this->documentManager->publish($page, $this->locale);
        $this->documentManager->flush();

        return $page;
    }
}
