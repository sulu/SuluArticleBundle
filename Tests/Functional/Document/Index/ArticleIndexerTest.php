<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
        $this->indexer = $this->getContainer()->get('sulu_article.elastic_search.article_indexer');
        $this->indexer->clear();
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

        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $document = $documentManager->find($article['id'], $this->locale);
        $this->indexer->index($document);

        $viewDocument = $this->manager->find(ArticleViewDocument::class, $article['id'] . '-' . $this->locale);

        $this->assertEquals($page->getUuid(), $viewDocument->getParentPageUuid());
    }

    /**
     * Create a new article.
     *
     * @param array $data
     * @param string $title
     * @param string $template
     *
     * @return mixed
     */
    private function createArticle(array $data, $title = 'Test Article', $template = 'default')
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
}
