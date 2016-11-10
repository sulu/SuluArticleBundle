<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * Functional testcases for Article API.
 */
class ArticleControllerTest extends SuluTestCase
{
    private static $typeMap = ['default' => 'blog', 'simple' => 'video'];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->initPhpcr();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    public function testPostWithoutAuthors($title = 'Test-Article', $template = 'default')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de',
            ['title' => $title, 'template' => $template, 'authored' => '2016-01-01']
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals(self::$typeMap[$template], $response['type']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals(new \DateTime('2016-01-01'), new \DateTime($response['authored']));
        $this->assertEquals([$this->getTestUser()->getContact()->getId()], $response['authors']);

        return $response;
    }

    public function testPost($title = 'Test-Article', $template = 'default')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de',
            ['title' => $title, 'template' => $template, 'authored' => '2016-01-01', 'authors' => [1, 2, 3]]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals(self::$typeMap[$template], $response['type']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals(new \DateTime('2016-01-01'), new \DateTime($response['authored']));
        $this->assertEquals([1, 2, 3], $response['authors']);

        return $response;
    }

    public function testGet()
    {
        $article = $this->testPost();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles/' . $article['id'] . '?locale=de');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        foreach ($article as $name => $value) {
            $this->assertEquals($value, $response[$name]);
        }
    }

    public function testPut($title = 'Sulu is awesome')
    {
        $article = $this->testPost();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=de',
            ['title' => $title, 'template' => 'default', 'authored' => '2016-01-01', 'authors' => [1, 3]]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals(new \DateTime('2016-01-01'), new \DateTime($response['authored']));
        $this->assertEquals([1, 3], $response['authors']);
    }

    public function testPutExtensions(
        $title = 'Sulu is awesome',
        $extensions = [
            'seo' => [
                'title' => 'Seo title',
                'description' => 'Seo description',
                'keywords' => 'Seo keywords',
                'canonicalUrl' => 'http://canonical.lo',
                'hideInSitemap' => true,
                'noFollow' => false,
                'noIndex' => true,
            ],
            'excerpt' => [
                'title' => 'Excerpt title',
                'description' => 'Excerpt title',
                'more' => 'Excerpt more',
                'categories' => [1],
                'tags' => [
                    'Excerpt',
                    'Tags',
                ],
                'icon' => [
                    'displayOption' => 'top',
                    'ids' => [1],
                ],
                'images' => [
                    'displayOption' => 'top',
                    'ids' => [1],
                ],
            ],
        ]
    ) {
        $media = $this->createMedia();

        $extensions['excerpt']['icon']['ids'] = [$media->getId()];
        $extensions['excerpt']['images']['ids'] = [$media->getId()];

        $article = $this->testPost();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=de',
            [
                'title' => $title,
                'template' => 'default',
                'ext' => $extensions,
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals($extensions['seo'], $response['ext']['seo']);
        $this->assertEquals($extensions['excerpt'], $response['ext']['excerpt']);
    }

    public function testPutDifferentTemplate($title = 'Sulu', $description = 'Sulu is awesome')
    {
        $article = $this->testPost();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=de',
            ['title' => $title, 'description' => $description, 'template' => 'simple']
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals('simple', $response['template']);
        $this->assertEquals(self::$typeMap['simple'], $response['type']);
        $this->assertEquals($description, $response['description']);
    }

    public function testPutDifferentLocale($title = 'Sulu is awesome')
    {
        $article = $this->testPost();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=en',
            ['title' => $title, 'template' => 'default']
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['de', 'en'], $response['concreteLanguages']);
        $this->assertEquals($title, $response['title']);
    }

    public function testCGet()
    {
        $this->purgeIndex();

        $article1 = $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome');
        $this->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles?locale=de&type=blog');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = array_map(
            function ($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title']], $items);
        $this->assertContains([$article2['id'], $article2['title']], $items);
    }

    public function testCGetIds()
    {
        $this->purgeIndex();

        $article1 = $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome');
        $this->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/articles?locale=de&ids=' . implode(',', [$article2['id'], $article1['id']])
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $this->assertContains($article2['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertContains($article1['id'], $response['_embedded']['articles'][1]['id']);
    }

    public function testCGetSearch()
    {
        $this->purgeIndex();

        $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome');
        $this->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles?locale=de&searchFields=title&search=awesome&type=blog');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);
        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($article2['title'], $response['_embedded']['articles'][0]['title']);
    }

    public function testCGetSearchCaseInsensitive()
    {
        $this->purgeIndex();

        $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome');
        $this->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles?locale=de&searchFields=title&search=AwEsoMe&type=blog');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);
        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($article2['title'], $response['_embedded']['articles'][0]['title']);
    }

    public function testCGetSort()
    {
        $this->purgeIndex();

        $article1 = $this->testPost('Hikaru Sulu');
        $this->flush();
        $article2 = $this->testPost('USS Enterprise');
        $this->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles?locale=de&sortBy=title&sortOrder=desc&type=blog');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);
        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($article2['title'], $response['_embedded']['articles'][0]['title']);
        $this->assertEquals($article1['id'], $response['_embedded']['articles'][1]['id']);
        $this->assertEquals($article1['title'], $response['_embedded']['articles'][1]['title']);
    }

    public function testCGetTypes()
    {
        $this->purgeIndex();

        $article1 = $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome', 'simple');
        $this->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles?locale=de&type=blog');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        $items = array_map(
            function ($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title']], $items);

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles?locale=de&type=video');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        $items = array_map(
            function ($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article2['id'], $article2['title']], $items);
    }

    public function testDelete()
    {
        $this->purgeIndex();

        $article = $this->testPost('Sulu');
        $this->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/articles/' . $article['id']);
        $this->flush();

        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request('GET', '/api/articles?locale=de&type=blog');

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $response['total']);
        $this->assertCount(0, $response['_embedded']['articles']);
    }

    public function testCDelete()
    {
        $this->purgeIndex();

        $article1 = $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome', 'simple');
        $this->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/articles?ids=' . implode(',', [$article1['id'], $article2['id']]));
        $this->flush();

        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request('GET', '/api/articles?locale=de&type=blog');

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $response['total']);
        $this->assertCount(0, $response['_embedded']['articles']);
    }

    /**
     * @return Media
     */
    private function createMedia()
    {
        $collection = new Collection();
        $collection->setType($this->getEntityManager()->getReference(CollectionType::class, 1));
        $media = new Media();
        $media->setType($this->getEntityManager()->getReference(MediaType::class, 1));
        $media->setCollection($collection);

        $this->getEntityManager()->persist($collection);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        return $media;
    }

    private function purgeIndex()
    {
        /** @var IndexerInterface $indexer */
        $indexer = $this->getContainer()->get('sulu_article.elastic_search.article_indexer');
        $indexer->clear();
    }

    private function flush()
    {
        /** @var IndexerInterface $indexer */
        $indexer = $this->getContainer()->get('sulu_article.elastic_search.article_indexer');
        $indexer->flush();
    }
}
