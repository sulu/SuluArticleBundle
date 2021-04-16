<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Document\Index;

use ONGR\ElasticsearchBundle\Service\Manager;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\ArticleIndexer;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class ArticleIndexerTest extends SuluTestCase
{
    /**
     * @var string
     */
    private $locale = 'en';

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var ArticleIndexer
     */
    private $indexer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->initPhpcr();
        $this->purgeDatabase();

        $this->manager = $this->getContainer()->get('es.manager.live');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->indexer = $this->getContainer()->get('sulu_article.elastic_search.article_live_indexer');
        $this->indexer->clear();
    }

    public function testIndexDefaultWithRoute()
    {
        $article = $this->createArticle(
            [
                'article' => 'Test content',
            ],
            'Test Article',
            'default_with_route'
        );

        $document = $this->documentManager->find($article['id'], $this->locale);
        $this->indexer->index($document);

        $viewDocument = $this->findViewDocument($article['id']);
        $this->assertEquals($document->getUuid(), $viewDocument->getUuid());
        $this->assertEquals('/articles/test-article', $viewDocument->getRoutePath());
        $this->assertInstanceOf('\DateTime', $viewDocument->getPublished());
        $this->assertTrue($viewDocument->getPublishedState());
        $this->assertEquals('localized', $viewDocument->getLocalizationState()->state);
        $this->assertNull($viewDocument->getLocalizationState()->locale);
    }

    public function testIndexShadow()
    {
        $article = $this->createArticle(
            [
                'article' => 'Test content',
            ],
            'Test Article',
            'default_with_route'
        );

        $secondLocale = 'de';

        // now add second locale
        $this->updateArticle(
            $article['id'],
            $secondLocale,
            [
                'id' => $article['id'],
                'article' => 'Test Inhalt',
            ],
            'Test Artikel Deutsch',
            'default_with_route'
        );

        // now transform second locale to shadow
        $this->updateArticle(
            $article['id'],
            $secondLocale,
            [
                'id' => $article['id'],
                'shadowOn' => true,
                'shadowBaseLanguage' => 'en',
            ],
            null,
            null
        );

        $viewDocument = $this->findViewDocument($article['id'], $secondLocale);

        $this->assertEquals($article['id'], $viewDocument->getUuid());
        $this->assertEquals('/articles/test-artikel-deutsch', $viewDocument->getRoutePath());
        $this->assertInstanceOf('\DateTime', $viewDocument->getPublished());
        $this->assertTrue($viewDocument->getPublishedState());
        $this->assertEquals('shadow', $viewDocument->getLocalizationState()->state);
        $this->assertEquals($this->locale, $viewDocument->getLocalizationState()->locale);
        $this->assertEquals($secondLocale, $viewDocument->getLocale());
        $this->assertEquals('Test Article', $viewDocument->getTitle());

        $contentData = json_decode($viewDocument->getContentData(), true);
        $this->assertEquals($contentData['article'], 'Test content');

        // now update the source locale
        // the shadow should be update also
        $this->updateArticle(
            $article['id'],
            $this->locale,
            [
                'id' => $article['id'],
                'article' => 'Test content - CHANGED!',
            ],
            'Test Article - CHANGED!',
            'default_with_route'
        );

        $viewDocument = $this->findViewDocument($article['id'], $secondLocale);
        $this->assertEquals('Test Article - CHANGED!', $viewDocument->getTitle());

        $contentData = json_decode($viewDocument->getContentData(), true);
        $this->assertEquals($contentData['article'], 'Test content - CHANGED!');
    }

    public function testIndexPageTreeRoute()
    {
        $page = $this->createPage();
        $article = $this->createArticle(
            [
                'routePath' => [
                    'page' => ['uuid' => $page->getUuid(), 'path' => $page->getResourceSegment()],
                    'path' => $page->getResourceSegment() . '/test-article',
                ],
            ],
            'Test Article',
            'page_tree_route'
        );

        $document = $this->documentManager->find($article['id'], $this->locale);
        $this->indexer->index($document);

        $viewDocument = $this->findViewDocument($article['id']);
        $this->assertEquals($page->getUuid(), $viewDocument->getParentPageUuid());
    }

    public function testSetUnpublished()
    {
        $article = $this->createArticle();

        $viewDocument = $this->indexer->setUnpublished($article['id'], $this->locale);
        $this->assertNull($viewDocument->getPublished());
        $this->assertFalse($viewDocument->getPublishedState());
    }

    public function testIndexTaggedProperties(): void
    {
        $data = [
            'title' => 'Test Article Title',
            'pageTitle' => 'Test Page Title',
            'article' => '<p>Test Article</p>',
            'article_2' => '<p>should not be indexed</p>',
            'blocks' => [
                [
                    'type' => 'title-with-article',
                    'title' => 'Test Title in Block',
                    'article' => '<p>Test Article in Block</p>',
                ],
            ],
        ];

        $article = $this->createArticle($data, $data['title'], 'default_with_search_tags');
        $this->documentManager->clear();

        $document = $this->documentManager->find($article['id'], $this->locale);
        $this->indexer->index($document);
        $this->indexer->flush();

        $viewDocument = $this->findViewDocument($article['id']);
        $contentFields = $viewDocument->getContentFields();

        $this->assertSame($article['id'], $viewDocument->getUuid());
        $this->assertSame($data, json_decode($viewDocument->getContentData(), true));

        $this->assertCount(5, $contentFields);
        $this->assertContains('Test Article Title', $contentFields);
        $this->assertContains('Test Page Title', $contentFields);
        $this->assertContains('Test Article', $contentFields);
        $this->assertContains('Test Title in Block', $contentFields);
        $this->assertContains('Test Article in Block', $contentFields);
    }

    public function testIndexContentData()
    {
        $data = [
            'title' => 'Test Article',
            'pageTitle' => 'Test Page Title',
            'article' => 'Test Article',
            'routePath' => '/test-article',
        ];

        $article = $this->createArticle($data, $data['title'], 'default_pages');
        $this->documentManager->clear();

        $this->createArticlePage($article);
        $this->documentManager->clear();

        $document = $this->documentManager->find($article['id'], $this->locale);
        $this->indexer->index($document);
        $this->indexer->flush();

        $viewDocument = $this->findViewDocument($article['id']);
        $this->assertEquals($article['id'], $viewDocument->getUuid());
        $this->assertEquals($data, json_decode($viewDocument->getContentData(), true));

        $this->assertProxies($data, $viewDocument->getContent(), $viewDocument->getView());

        $this->assertCount(1, $viewDocument->getPages());
        foreach ($viewDocument->getPages() as $page) {
            $this->assertProxies(
                [
                    'title' => 'Test Article',
                    'pageTitle' => 'Test-Page',
                    'article' => '',
                    'routePath' => '/test-article/page-2',
                ],
                $page->content,
                $page->view
            );
        }
    }

    private function assertProxies(array $data, $contentProxy, $viewProxy)
    {
        $this->assertInstanceOf(\ArrayObject::class, $contentProxy);
        $this->assertInstanceOf(\ArrayObject::class, $viewProxy);

        $content = iterator_to_array($contentProxy);
        $view = iterator_to_array($viewProxy);

        $this->assertEquals($data, $content);
        foreach ($data as $key => $value) {
            $this->assertArrayHasKey($key, $view);
        }
    }

    /**
     * Create a new article.
     *
     * @param string $title
     * @param string $template
     *
     * @return mixed
     */
    private function createArticle(array $data = [], $title = 'Test Article', $template = 'default')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=' . $this->locale . '&action=publish',
            array_merge(['title' => $title, 'template' => $template], $data)
        );

        return json_decode($client->getResponse()->getContent(), true);
    }

    /**
     * Update existing article.
     *
     * @param string $uuid
     * @param string $locale
     * @param string $title
     * @param string $template
     *
     * @return mixed
     */
    private function updateArticle(
        $uuid,
        $locale = null,
        array $data = [],
        $title = 'Test Article',
        $template = 'default'
    ) {
        $requestData = $data;

        if ($title) {
            $requestData['title'] = $title;
        }

        if ($template) {
            $requestData['template'] = $template;
        }

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $uuid . '?locale=' . ($locale ? $locale : $this->locale) . '&action=publish',
            $requestData
        );

        $this->assertEquals('200', $client->getResponse()->getStatusCode());

        return json_decode($client->getResponse()->getContent(), true);
    }

    /**
     * Create article page.
     *
     * @param string $pageTitle
     * @param string $template
     *
     * @return array
     */
    private function createArticlePage(
        array $article,
        array $data = [],
        $pageTitle = 'Test-Page',
        $template = 'default_pages'
    ) {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles/' . $article['id'] . '/pages?locale=' . $this->locale . '&action=publish',
            array_merge(['pageTitle' => $pageTitle, 'template' => $template], $data)
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

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
    private function createPage($title = 'Test Page', $resourceSegment = '/test-page', $locale = 'de')
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

        $this->documentManager->persist($page, $locale);
        $this->documentManager->publish($page, $locale);
        $this->documentManager->flush();

        return $page;
    }

    /**
     * Find view-document.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return ArticleViewDocument
     */
    private function findViewDocument($uuid, $locale = null)
    {
        return $this->manager->find(
            $this->getContainer()->getParameter('sulu_article.view_document.article.class'),
            $uuid . '-' . ($locale ? $locale : $this->locale)
        );
    }
}
