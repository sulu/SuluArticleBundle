<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Twig;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\ArticleBundle\Twig\ArticleViewDocumentTwigExtension;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ArticleViewDocumentTwigExtensionTest extends SuluTestCase
{
    const LOCALE = 'de';

    /**
     * @var KernelBrowser
     */
    protected $client;


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
            $this->createArticle('XXX 3 - in another webspace', 'default', 'test', []),
        ];

        $this->pushFakeRequest('sulu_io', $items[0]['id']);

        // test 'loadRecent' => all others should be returned
        $result = $this->getTwigExtension()->loadRecent();
        $this->assertCount(3, $result);

        // test 'loadSimilar' => only article with title 'XXX 2' should be returned
        $result = $this->getTwigExtension()->loadSimilar();
        $this->assertCount(1, $result);
        $this->assertEquals($items[1]['title'], $result[0]->getTitle());
    }

    public function testFindMethodsWithIgnoreWebspaces()
    {
        $items = [
            $this->createArticle('XXX 1'),
            $this->createArticle('XXX 2'),
            $this->createArticle('YYY 3'),
            $this->createArticle('YYY 4'),
            $this->createArticle('XXX 3 - in another webspace', 'default', 'test', []),
        ];

        $this->pushFakeRequest('sulu_io', $items[0]['id']);

        // test 'loadRecent' => all others should be returned
        $result = $this->getTwigExtension()->loadRecent(10, null, null, true);
        $this->assertCount(4, $result);

        // test 'loadSimilar' => only article with title 'XXX 2' and 'XXX 3' should be returned
        $result = $this->getTwigExtension()->loadSimilar(10, null, null, true);
        $this->assertCount(2, $result);
        $this->assertEquals($items[1]['title'], $result[0]->getTitle());
        $this->assertEquals($items[4]['title'], $result[1]->getTitle());
    }

    private function createArticle(
        $title = 'Test-Article',
        $template = 'default',
        $mainWebspace = null,
        $additionalWebspaces = null
    ) {
        $data = [
            'title' => $title,
            'template' => $template,
        ];

        if ($mainWebspace) {
            $data['mainWebspace'] = $mainWebspace;
            $data['additionalWebspaces'] = $additionalWebspaces;
        }

        $this->client->request(
            'POST',
            '/api/articles?locale=' . self::LOCALE . '&action=publish',
            $data
        );

        $respone = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $respone);

        return json_decode($respone->getContent(), true);
    }

    private function pushFakeRequest($webspaceKey, $id)
    {
        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $webspace = $webspaceManager->findWebspaceByKey($webspaceKey);

        $fakeRequest = Request::create('/', 'GET');
        $fakeRequest->setLocale('de');
        $fakeRequest->attributes->set('_sulu', new RequestAttributes(
            [
                'webspace' => $webspace,
            ]
        ));
        $fakeRequest->attributes->set('object', $this->getArticleDocument($id));

        /** @var RequestStack $requestStack */
        $requestStack = $this->getContainer()->get('request_stack');
        $requestStack->push($fakeRequest);
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
