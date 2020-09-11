<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Export;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ArticleImportTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createAuthenticatedClient();

        $this->initPhpcr();
        $this->purgeDatabase();
    }

    public function testImport(): void
    {
        $article = $this->createArticle('Test-Article');

        $import = $this->getContainer()->get('sulu_article.import.importer');

        $fileContent = file_get_contents(__DIR__ . '/export.xliff');
        $fileContent = str_replace('%uuid%', $article['id'], $fileContent);
        file_put_contents(__DIR__ . '/export.test.xliff', $fileContent);

        $result = $import->import('de', __DIR__ . '/export.test.xliff', null, '1.2.xliff', true);

        $this->assertSame(1, $result->getCount());
        $this->assertSame(0, $result->getFails());
        $this->assertSame(1, $result->getSuccesses());
        $this->assertSame([], $result->getFailed());
        $this->assertSame([], $result->getExceptionStore());

        $result = $this->getArticle($article['id']);

        $this->assertSame('simple', $result['template']);
        $this->assertSame('TEST TITLE', $result['title']);
        $this->assertSame('<p>TEST DESCRIPTION</p>', $result['description']);

        $this->assertSame('SEO TITLE', $result['ext']['seo']['title']);
        $this->assertSame('SEO DESCRIPTION', $result['ext']['seo']['description']);
        $this->assertSame('SEO KEYWORDS', $result['ext']['seo']['keywords']);
        $this->assertSame('http://sulu.io', $result['ext']['seo']['canonicalUrl']);
        $this->assertSame(true, $result['ext']['seo']['noIndex']);
        $this->assertSame(true, $result['ext']['seo']['noFollow']);
        $this->assertSame(true, $result['ext']['seo']['hideInSitemap']);

        $this->assertSame('EXCERPT TITLE', $result['ext']['excerpt']['title']);
        $this->assertSame('EXCERPT MORE', $result['ext']['excerpt']['more']);
        $this->assertSame('EXCERPT DESCRIPTION', $result['ext']['excerpt']['description']);
        $this->assertSame(['displayOption' => 'left', 'ids' => [6, 3]], $result['ext']['excerpt']['icon']);
        $this->assertSame(['displayOption' => 'left', 'ids' => [6, 3]], $result['ext']['excerpt']['images']);
    }

    private function createArticle(
        string $title = 'Test-Article',
        string $template = 'default',
        array $data = []
    ): array {
        $this->client->request(
            'POST',
            '/api/articles?locale=en&action=publish',
            array_merge($data, ['title' => $title, 'template' => $template])
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    private function getArticle(string $id, string $locale = 'de'): array
    {
        $this->client->request('GET', '/api/articles/' . $id . '?locale=' . $locale);

        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
