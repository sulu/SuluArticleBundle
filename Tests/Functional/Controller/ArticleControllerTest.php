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

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManager;

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

    public function testPostWithoutAuthor($title = 'Test-Article', $template = 'default')
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
        $this->assertEquals(self::$typeMap[$template], $response['articleType']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals(new \DateTime('2016-01-01'), new \DateTime($response['authored']));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);

        return $response;
    }

    public function testPost($title = 'Test-Article', $template = 'default')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de',
            [
                'title' => $title,
                'template' => $template,
                'authored' => '2016-01-01',
                'author' => $this->getTestUser()->getContact()->getId(),
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals(self::$typeMap[$template], $response['articleType']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals(new \DateTime('2016-01-01'), new \DateTime($response['authored']));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);

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

    public function testPut($title = 'Sulu is awesome', $locale = 'de', $article = null)
    {
        if (!$article) {
            $article = $this->testPost();
        }

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=' . $locale,
            [
                'title' => $title,
                'template' => 'default',
                'authored' => '2016-01-01',
                'author' => $this->getTestUser()->getContact()->getId(),
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals(new \DateTime('2016-01-01'), new \DateTime($response['authored']));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);

        return $article;
    }

    public function testPutTranslation()
    {
        $article = $this->testPut('Sulu ist toll', 'de');
        $this->testPut('Sulu is nice', 'en', $article);
    }

    public function testGetGhost()
    {
        $title = 'Sulu ist toll';
        $article = $this->testPut($title, 'de');

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles/' . $article['id'] . '?locale=en');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals(new \DateTime('2016-01-01'), new \DateTime($response['authored']));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);
        $this->assertEquals(['name' => 'ghost', 'value' => 'de'], $response['type']);
    }

    public function testCGetGhost()
    {
        $this->purgeIndex();

        $title1 = 'Sulu ist toll - Test 1';
        $article1 = $this->testPut($title1, 'de');

        $title2 = 'Sulu ist toll - Test 2';
        $article2 = $this->testPut($title2, 'de');
        $title2_EN = $title2 . ' (EN)';
        $this->testPut($title2_EN, 'en', $article2);

        $client = $this->createAuthenticatedClient();

        // Retrieve articles in 'de'.
        $client->request('GET', '/api/articles?locale=de&type=blog');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = array_map(
            function ($item) {
                return [$item['uuid'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $title1], $items);
        $this->assertContains([$article2['id'], $title2], $items);

        // Retrieve articles in 'en'.
        $client->request('GET', '/api/articles?locale=en&type=blog');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = array_map(
            function ($item) {
                return [$item['uuid'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $title1], $items);
        $this->assertContains([$article2['id'], $title2_EN], $items);
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
        $this->assertEquals(self::$typeMap['simple'], $response['articleType']);
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
                return [$item['uuid'], $item['title']];
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
        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['uuid']);
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
        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['uuid']);
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
        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['uuid']);
        $this->assertEquals($article2['title'], $response['_embedded']['articles'][0]['title']);
        $this->assertEquals($article1['id'], $response['_embedded']['articles'][1]['uuid']);
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
                return [$item['uuid'], $item['title']];
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
                return [$item['uuid'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article2['id'], $article2['title']], $items);
    }

    public function testCGetFilterByContactId()
    {
        $this->purgeDatabase();

        /** @var UserManager $userManager */
        $userManager = $this->getContainer()->get('sulu_security.user_manager');
        $contactManager = $this->getContainer()->get('sulu_contact.contact_manager');
        /** @var DocumentManager $documentManager */
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        // create contact1
        $contact1 = $contactManager->save(
            [
                'firstName' => 'Testi 1',
                'lastName' => 'Testo 1',
            ],
            null,
            false,
            true
        );

        // create contact2
        $contact2 = $contactManager->save(
            [
                'firstName' => 'Testi 2',
                'lastName' => 'Testo 2',
            ],
            null,
            false,
            true
        );

        // create contact3
        $contact3 = $contactManager->save(
            [
                'firstName' => 'Testi 3',
                'lastName' => 'Testo 3',
            ],
            null,
            false,
            true
        );

        // create user1
        $user1 = $userManager->save(
            [
                'username' => 'testi.testo1',
                'email' => 'testi.testo1@LOL.xyz',
                'password' => 'ThisIsSave!#123',
                'contact' => [
                    'id' => $contact1->getId(),
                ],
            ],
            'de',
            null,
            false,
            true
        );

        // create user2
        $user2 = $userManager->save(
            [
                'username' => 'testi.testo2',
                'email' => 'testi.testo2@LOL.xyz',
                'password' => 'ThisIsSave!#123',
                'contact' => [
                    'id' => $contact2->getId(),
                ],
            ],
            'de',
            null,
            false,
            true
        );

        // create user3
        $user3 = $userManager->save(
            [
                'username' => 'testi.testo3',
                'email' => 'testi.testo3@LOL.xyz',
                'password' => 'ThisIsSave!#123',
                'contact' => [
                    'id' => $contact3->getId(),
                ],
            ],
            'de',
            null,
            false,
            true
        );

        /** @var ArticleDocument $article */
        $article = $documentManager->create('article');
        $article->setTitle('first title');
        $article->setStructureType('default');
        $article->setAuthor($contact3->getId());

        $documentManager->persist($article, 'de', ['user' => $user1->getId()]);
        $documentManager->publish($article, 'de');
        $documentManager->flush();

        $documentManager->persist($article, 'de', ['user' => $user2->getId()]);
        $documentManager->publish($article, 'de');
        $documentManager->flush();

        // create client
        $client = $this->createAuthenticatedClient();

        // retrieve all articles for user1
        $client->request(
            'GET',
            '/api/articles?locale=de&searchFields=title&type=blog&contactId=' . $user1->getContact()->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        // retrieve all articles for user2
        $client->request(
            'GET',
            '/api/articles?locale=de&searchFields=title&type=blog&contactId=' . $user2->getContact()->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        // add article
        /** @var ArticleDocument $article */
        $article2 = $documentManager->create('article');
        $article2->setTitle('first title');
        $article2->setStructureType('default');

        $documentManager->persist($article2, 'de', ['user' => $user1->getId()]);
        $documentManager->publish($article2, 'de');
        $documentManager->flush();

        // retrieve all articles for user1
        $client->request(
            'GET',
            '/api/articles?locale=de&searchFields=title&type=blog&contactId=' . $user1->getContact()->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        // retrieve all articles for user2
        $client->request(
            'GET',
            '/api/articles?locale=de&searchFields=title&type=blog&contactId=' . $user2->getContact()->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        // retrieve all articles for user3
        $client->request(
            'GET',
            '/api/articles?locale=de&searchFields=title&type=blog&contactId=' . $user3->getContact()->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);
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

    public function testCopyLocale()
    {
        // prepare vars
        $client = $this->createAuthenticatedClient();
        $locale = 'de';
        $destLocale = 'en';

        $this->purgeIndex();

        // create article in default locale
        $article1 = $this->testPost('Sulu ist toll - Artikel 1');
        $article2 = $this->testPost('Sulu ist toll - Artikel 2');
        $this->flush();

        // get all articles in default locale
        $client->request('GET', '/api/articles?locale=' . $locale . '&type=blog');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = array_map(
            function ($item) {
                return [$item['uuid'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title']], $items);
        $this->assertContains([$article2['id'], $article2['title']], $items);

        // get all articles in dest locale (both should be ghosts)
        $client->request('GET', '/api/articles?locale=' . $destLocale . '&type=blog');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = array_map(
            function ($item) {
                return [$item['uuid'], $item['title'], $item['localizationState']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title'], ['state' => 'ghost', 'locale' => 'de']], $items);
        $this->assertContains([$article2['id'], $article2['title'], ['state' => 'ghost', 'locale' => 'de']], $items);

        // request copy-locale post action for article1
        $client->request('POST', '/api/articles/' . $article1['id'] . '?locale=' . $locale . '&dest=' . $destLocale . '&action=copy-locale');
        $this->assertHttpStatusCode(200, $client->getResponse());

        // get all articles in dest locale (now only one should be a ghost)
        $client->request('GET', '/api/articles?locale=' . $destLocale . '&type=blog');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = array_map(
            function ($item) {
                return [$item['uuid'], $item['title'], $item['localizationState']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title'], ['state' => 'localized']], $items);
        $this->assertContains([$article2['id'], $article2['title'], ['state' => 'ghost', 'locale' => 'de']], $items);
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
