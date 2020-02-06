<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Sitemap;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ArticleBundle\Sitemap\ArticleSitemapProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;

class ArticleSitemapProviderTest extends SuluTestCase
{
    /**
     * @var ArticleSitemapProvider
     */
    protected $articleSitemapProvider;

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

        $this->articleSitemapProvider = $this->getContainer()->get('sulu_article.sitemap.articles');
    }

    public function testBuild()
    {
        $article1 = $this->createArticle('1', 'default', 'sulu_io', []);
        $article2 = $this->createArticle('2', 'simple', 'test', ['sulu_io']);
        $article3 = $this->createArticle('3', 'default', 'test', ['test-2']);

        /** @var SitemapUrl[] $result */
        $result = $this->articleSitemapProvider->build(0, 'sulucmf_at');
        $this->assertCount(2, $result);
        $this->assertEquals($article1['route'], $result[0]->getLoc());
        $this->assertEquals($article2['route'], $result[1]->getLoc());

        /** @var SitemapUrl[] $result */
        $result = $this->articleSitemapProvider->build(0, 'test');
        $this->assertCount(2, $result);
        $this->assertEquals($article2['route'], $result[0]->getLoc());
        $this->assertEquals($article3['route'], $result[1]->getLoc());

        /** @var SitemapUrl[] $result */
        $result = $this->articleSitemapProvider->build(0, 'test-2');
        $this->assertCount(1, $result);
        $this->assertEquals($article3['route'], $result[0]->getLoc());
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
            'mainWebspace' => $mainWebspace,
            'additionalWebspaces' => $additionalWebspaces,
        ];

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de&action=publish',
            array_merge($data, ['title' => $title, 'template' => $template])
        );

        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        return json_decode($client->getResponse()->getContent(), true);
    }
}
