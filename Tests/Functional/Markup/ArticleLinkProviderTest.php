<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Markup;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ArticleBundle\Markup\ArticleLinkProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ArticleLinkProviderTest extends SuluTestCase
{
    /**
     * @var ArticleLinkProvider
     */
    private $articleLinkProvider;

    /**
     * @var KernelBrowser
     */
    private $client;

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

        $this->articleLinkProvider = $this->getContainer()->get('sulu_article.markup.link_provider');
    }

    public function testPreload()
    {
        $articles = [
            $this->createArticle(),
            $this->createAndPublishArticle(),
        ];

        $uuids = \array_map(
            function(array $data) {
                return $data['id'];
            },
            $articles
        );

        $result = $this->articleLinkProvider->preload($uuids, 'de', false);

        $this->assertCount(2, $result);
        $this->assertEquals($articles[0]['id'], $result[0]->getId());
        $this->assertEquals('http://test.localhost' . $articles[0]['route'], $result[0]->getUrl());
        $this->assertFalse($result[0]->isPublished());
        $this->assertEquals($articles[1]['id'], $result[1]->getId());
        $this->assertEquals('http://test.localhost' . $articles[1]['route'], $result[1]->getUrl());
        $this->assertTrue($result[1]->isPublished());
    }

    public function testPreloadPublished()
    {
        $articles = [
            $this->createArticle(),
            $this->createAndPublishArticle(),
        ];

        $uuids = \array_map(
            function(array $data) {
                return $data['id'];
            },
            $articles
        );

        $result = $this->articleLinkProvider->preload($uuids, 'de');

        $this->assertCount(1, $result);
        $this->assertEquals($articles[1]['id'], $result[0]->getId());
    }

    public function testPreloadMoreThan10()
    {
        $articles = [
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
        ];

        $uuids = \array_map(
            function(array $data) {
                return $data['id'];
            },
            $articles
        );

        $result = $this->articleLinkProvider->preload($uuids, 'de', false);

        $this->assertCount(11, $result);
    }

    private function createArticle($title = 'Test-Article', $template = 'default', $data = [])
    {
        $this->client->jsonRequest(
            'POST',
            '/api/articles?locale=de',
            \array_merge($data, ['title' => $title, 'template' => $template])
        );

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    private function createAndPublishArticle($title = 'Test-Article', $template = 'default', $data = [])
    {
        $this->client->jsonRequest(
            'POST',
            '/api/articles?locale=de&action=publish',
            \array_merge($data, ['title' => $title, 'template' => $template])
        );

        return \json_decode($this->client->getResponse()->getContent(), true);
    }
}
