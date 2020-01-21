<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Controller;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManager;

/**
 * Functional testcases for article version API.
 */
class VersionControllerTest extends SuluTestCase
{
    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var string
     */
    protected $locale = 'de';

    public function setUp(): void
    {
        if (!$this->getContainer()->getParameter('sulu_document_manager.versioning.enabled')) {
            $this->markTestSkipped('Versioning is not enabled');
        }

        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
    }

    public function testPostRestore()
    {
        /** @var ArticleDocument $article */
        $article = $this->documentManager->create('article');
        $article->setTitle('first title');
        $article->setStructureType('default');

        $this->documentManager->persist($article, $this->locale);
        $this->documentManager->publish($article, $this->locale);
        $this->documentManager->flush();

        $article = $this->documentManager->find($article->getUuid(), $this->locale);
        $article->setTitle('second title');
        $this->documentManager->persist($article, $this->locale);
        $this->documentManager->publish($article, $this->locale);
        $this->documentManager->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/articles/' . $article->getUuid() . '/versions/1_0?action=restore&locale=' . $this->locale
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('first title', $response['title']);
    }

    public function testPostRestoreInvalidVersion()
    {
        /** @var ArticleDocument $article */
        $article = $this->documentManager->create('article');
        $article->setTitle('first title');
        $article->setStructureType('default');

        $this->documentManager->persist($article, $this->locale);
        $this->documentManager->publish($article, $this->locale);
        $this->documentManager->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/articles/' . $article->getUuid() . '/versions/2_0?action=restore&locale=' . $this->locale
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testCGet()
    {
        /** @var ArticleDocument $article */
        $article = $this->documentManager->create('article');
        $article->setTitle('first title');
        $article->setStructureType('default');

        $this->documentManager->persist($article, $this->locale);
        $this->documentManager->publish($article, $this->locale);
        $this->documentManager->flush();

        $article = $this->documentManager->find($article->getUuid(), $this->locale);
        $article->setTitle('second title');
        $this->documentManager->persist($article, $this->locale);
        $this->documentManager->publish($article, $this->locale);
        $this->documentManager->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/articles/' . $article->getUuid() . '/versions?locale=' . $this->locale
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);

        $versions = $response['_embedded']['versions'];
        $this->assertEquals('1_1', $versions[0]['id']);
        $this->assertEquals($this->locale, $versions[0]['locale']);
        $this->assertEquals('1_0', $versions[1]['id']);
        $this->assertEquals($this->locale, $versions[1]['locale']);
    }
}
