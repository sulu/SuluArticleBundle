<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Twig;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Twig\ArticleViewDocumentTwigExtension;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ArticleViewDocumentTwigExtensionTest extends SuluTestCase
{
    const LOCALE = 'de';

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
        $manager = $this->getContainer()->get('es.manager.live');
        $manager->dropAndCreateIndex();
    }

    public function testFindMethods()
    {
        $items = [
            $this->createArticle('XXX 1'),
            $this->createArticle('XXX 2'),
            $this->createArticle('YYY 3'),
            $this->createArticle('YYY 4'),
        ];

        $fakeRequest = Request::create('/', 'GET');
        $fakeRequest->setLocale('de');
        $fakeRequest->attributes->set('object', $this->getArticleDocument($items[0]['id']));

        $requestStack = $this->getContainer()->get('request_stack');
        $requestStack->push($fakeRequest);

        // test 'loadRecent' => all others should be returned
        $result = $this->getTwigExtension()->loadRecent();
        $this->assertCount(count($items) - 1, $result);

        // test 'loadSimilar' => only article with title 'XXX 2' should be returned
        $result = $this->getTwigExtension()->loadSimilar();
        $this->assertCount(1, $result);
        $this->assertEquals($items[1]['title'], $result[0]->getTitle());
    }

    private function createArticle($title = 'Test-Article', $template = 'default')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=' . self::LOCALE . '&action=publish',
            ['title' => $title, 'template' => $template]
        );

        return json_decode($client->getResponse()->getContent(), true);
    }

    /**
     * @param string $uuid
     *
     * @return ArticleDocument
     */
    private function getArticleDocument($uuid)
    {
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        return $documentManager->find($uuid, self::LOCALE);
    }

    /**
     * @return ArticleViewDocumentTwigExtension
     */
    private function getTwigExtension()
    {
        return $this->getContainer()->get('sulu_article.twig.view_document_repository');
    }
}
