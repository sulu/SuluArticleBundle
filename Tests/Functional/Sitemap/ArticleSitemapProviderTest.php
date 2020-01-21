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

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Sitemap;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ArticleBundle\Sitemap\ArticleSitemapProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Symfony\Component\BrowserKit\Client;

class ArticleSitemapProviderTest extends SuluTestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ArticleSitemapProvider
     */
    protected $articleSitemapProvider;

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

        $this->articleSitemapProvider = $this->getContainer()->get('sulu_article.sitemap.articles');
    }

    public function testBuild()
    {
        $article1 = $this->createArticle('1', 'default', 'sulu_io', []);
        $article2 = $this->createArticle('2', 'simple', 'test', ['sulu_io']);
        $article3 = $this->createArticle('3', 'default', 'test', ['test-2']);
        $article4 = $this->createArticle('4', 'default', 'sulu_io_blog', []);

        /** @var SitemapUrl[] $result */
        $result = $this->articleSitemapProvider->build(0, 'http', 'sulu_io.localhost');

        $this->assertCount(3, $result);
        $this->assertEquals('http://sulu_io.localhost' . $article1['route'], $result[0]->getLoc());
        $this->assertEquals('http://sulu_io.localhost/blog' . $article4['route'], $result[1]->getLoc());
        $this->assertEquals('http://sulu_io.localhost' . $article2['route'], $result[2]->getLoc());

        /** @var SitemapUrl[] $result */
        $result = $this->articleSitemapProvider->build(0, 'http', 'test.localhost');
        $this->assertCount(2, $result);
        $this->assertEquals('http://test.localhost' . $article2['route'], $result[0]->getLoc());
        $this->assertEquals('http://test.localhost' . $article3['route'], $result[1]->getLoc());

        /** @var SitemapUrl[] $result */
        $result = $this->articleSitemapProvider->build(0, 'http', 'test-2.localhost');
        $this->assertCount(1, $result);
        $this->assertEquals('http://test-2.localhost' . $article3['route'], $result[0]->getLoc());
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

        $this->client->request(
            'POST',
            '/api/articles?locale=de&action=publish',
            array_merge($data, ['title' => $title, 'template' => $template])
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
