<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Markup;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ArticleBundle\Markup\ArticleLinkProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ArticleLinkProviderTest extends SuluTestCase
{
    /**
     * @var ArticleLinkProvider
     */
    private $articleLinkProvider;

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

        $this->articleLinkProvider = $this->getContainer()->get('sulu_article.markup.link_provider');
    }

    public function testPreload()
    {
        $articles = [
            $this->createArticle(),
            $this->createAndPublishArticle(),
        ];

        $uuids = array_map(
            function (array $data) {
                return $data['id'];
            },
            $articles
        );

        $result = $this->articleLinkProvider->preload($uuids, 'de', false);

        $this->assertCount(2, $result);
        $this->assertEquals($articles[0]['id'], $result[0]->getId());
        $this->assertEquals('http://{host}' . $articles[0]['route'], $result[0]->getUrl());
        $this->assertFalse($result[0]->isPublished());
        $this->assertEquals($articles[1]['id'], $result[1]->getId());
        $this->assertEquals('http://{host}' . $articles[1]['route'], $result[1]->getUrl());
        $this->assertTrue($result[1]->isPublished());
    }

    public function testPreloadPublished()
    {
        $articles = [
            $this->createArticle(),
            $this->createAndPublishArticle(),
        ];

        $uuids = array_map(
            function (array $data) {
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

        $uuids = array_map(
            function (array $data) {
                return $data['id'];
            },
            $articles
        );

        $result = $this->articleLinkProvider->preload($uuids, 'de', false);

        $this->assertCount(11, $result);
    }

    private function createArticle($title = 'Test-Article', $template = 'default', $data = [])
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de',
            array_merge($data, ['title' => $title, 'template' => $template])
        );

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function createAndPublishArticle($title = 'Test-Article', $template = 'default', $data = [])
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de&action=publish',
            array_merge($data, ['title' => $title, 'template' => $template])
        );

        return json_decode($client->getResponse()->getContent(), true);
    }
}
