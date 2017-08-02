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

use Ferrandini\Urlizer;
use ONGR\ElasticsearchBundle\Service\Manager;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManager;

/**
 * Functional testcases for Article API.
 */
class ArticleControllerTest extends SuluTestCase
{
    use ArticleViewDocumentIdTrait;

    private static $typeMap = ['default' => 'blog', 'simple' => 'video'];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->initPhpcr();
        $this->purgeDatabase();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    protected function post($title = 'Test-Article', $template = 'default')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de',
            [
                'title' => $title,
                'template' => $template,
                'authored' => '2016-01-01',
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        return json_decode($client->getResponse()->getContent(), true);
    }

    protected function postPage($article, $pageTitle = 'Test-Page')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles/' . $article['id'] . '/pages?locale=de',
            [
                'pageTitle' => $pageTitle,
                'template' => $article['template'],
                'authored' => '2016-01-01',
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        return json_decode($client->getResponse()->getContent(), true);
    }

    public function testPost($title = 'Test-Article', $template = 'default')
    {
        $response = $this->post($title, $template);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals($this->getRoute($title), $response['route']);
        $this->assertEquals(self::$typeMap[$template], $response['articleType']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals('2016-01-01', date('Y-m-d', strtotime($response['authored'])));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);

        $this->assertNotNull($this->findViewDocument($response['id'], 'de'));

        return $response;
    }

    public function testPostWithAuthor($title = 'Sulu is awesome', $locale = 'de')
    {
        $user = $this->createContact();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=' . $locale,
            [
                'title' => $title,
                'template' => 'default',
                'authored' => '2016-01-01',
                'author' => $user->getId(),
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals($this->getRoute($title), $response['route']);
        $this->assertEquals('2016-01-01', date('Y-m-d', strtotime($response['authored'])));
        $this->assertEquals($user->getId(), $response['author']);

        $this->assertNotNull($this->findViewDocument($response['id'], 'de'));
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

    protected function put($title = 'Sulu is awesome', $locale = 'de', $article = null)
    {
        if (!$article) {
            $article = $this->post();
        }

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=' . $locale,
            [
                'title' => $title,
                'template' => 'default',
                'authored' => '2016-01-01',
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        return json_decode($client->getResponse()->getContent(), true);
    }

    public function testPut($title = 'Sulu is awesome', $locale = 'de', $article = null)
    {
        if (!$article) {
            $article = $this->testPost();
        }

        $response = $this->put($title, $locale, $article);
        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($article['route'], $response['route']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals('2016-01-01', date('Y-m-d', strtotime($response['authored'])));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);

        $this->assertNotNull($this->findViewDocument($response['id'], 'de'));

        return $article;
    }

    public function testPutWithAuthor($title = 'Sulu is awesome', $locale = 'de')
    {
        $user = $this->createContact();

        $article = $this->testPost();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=' . $locale,
            [
                'title' => $title,
                'template' => 'default',
                'authored' => '2016-01-01',
                'author' => $user->getId(),
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals($article['route'], $response['route']);
        $this->assertEquals('2016-01-01', date('Y-m-d', strtotime($response['authored'])));
        $this->assertEquals($user->getId(), $response['author']);

        $this->assertNotNull($this->findViewDocument($response['id'], 'de'));
    }

    public function testPutTranslation($title = 'Sulu is nice')
    {
        $article = $this->put('Sulu ist toll', 'de');
        $response = $this->put($title, 'en', $article);

        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($this->getRoute($title), $response['route']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals('2016-01-01', date('Y-m-d', strtotime($response['authored'])));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);

        $this->assertNotNull($this->findViewDocument($response['id'], 'de'));
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
        $this->assertEquals('2016-01-01', date('Y-m-d', strtotime($response['authored'])));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);
        $this->assertEquals(['name' => 'ghost', 'value' => 'de'], $response['type']);
    }

    public function testCGetGhost()
    {
        $this->purgeIndex();

        $title1 = 'Sulu ist toll - Test 1';
        $article1 = $this->put($title1, 'de');

        $title2 = 'Sulu ist toll - Test 2';
        $article2 = $this->put($title2, 'de');

        $title2_EN = $title2 . ' (EN)';
        $this->put($title2_EN, 'en', $article2);

        $client = $this->createAuthenticatedClient();

        // Retrieve articles in 'de'.
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
                return [$item['id'], $item['title']];
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
                'audience_targeting_groups' => [],
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

    public function testCGetSearchRoutePath()
    {
        $this->purgeIndex();

        $articles = [
            $this->testPost('Sulu'),
            $this->testPost('Sulu is awesome'),
        ];
        $this->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles?locale=de&searchFields=route_path&search=/articles&type=blog');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);
        $this->assertEquals($articles[0]['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($articles[0]['title'], $response['_embedded']['articles'][0]['title']);
        $this->assertEquals($articles[1]['id'], $response['_embedded']['articles'][1]['id']);
        $this->assertEquals($articles[1]['title'], $response['_embedded']['articles'][1]['title']);
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
        $client->request('GET', '/api/articles?locale=de&sortBy=title.raw&sortOrder=desc&type=blog');

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

        $this->assertNull($this->findViewDocument($article['id'], 'de'));
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

        $this->assertNull($this->findViewDocument($article1['id'], 'de'));
        $this->assertNull($this->findViewDocument($article2['id'], 'de'));
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
                return [$item['id'], $item['title']];
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
                return [$item['id'], $item['title'], $item['localizationState']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title'], ['state' => 'ghost', 'locale' => 'de']], $items);
        $this->assertContains([$article2['id'], $article2['title'], ['state' => 'ghost', 'locale' => 'de']], $items);

        // request copy-locale post action for article1
        $client->request(
            'POST',
            '/api/articles/' . $article1['id'] . '?locale=' . $locale . '&dest=' . $destLocale . '&action=copy-locale'
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($article1['id'], $response['id']);
        $this->assertEquals([$locale, $destLocale], $response['concreteLanguages']);

        // get all articles in dest locale (now only one should be a ghost)
        $client->request('GET', '/api/articles?locale=' . $destLocale . '&type=blog');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = array_map(
            function ($item) {
                return [$item['id'], $item['title'], $item['localizationState']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title'], ['state' => 'localized']], $items);
        $this->assertContains([$article2['id'], $article2['title'], ['state' => 'ghost', 'locale' => 'de']], $items);
    }

    public function testCgetFilterByCategory()
    {
        $title = 'Test-Article';
        $template = 'default';
        $category = $this->createCategory();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de',
            [
                'title' => $title,
                'template' => $template,
                'authored' => '2016-01-01',
                'ext' => ['excerpt' => ['categories' => [$category->getId()]]],
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $article1 = json_decode($client->getResponse()->getContent(), true);
        // create second article which should not appear in response
        $article2 = $this->post();

        $client->request('GET', '/api/articles?locale=de&categoryId=' . $category->getId());
        $this->assertHttpStatusCode(200, $client->getResponse());
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $result['_embedded']['articles']);
        $this->assertEquals($article1['id'], $result['_embedded']['articles'][0]['id']);
    }

    public function testCgetFilterByTag()
    {
        $title = 'Test-Article';
        $template = 'default';
        $tag = $this->createTag();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de',
            [
                'title' => $title,
                'template' => $template,
                'authored' => '2016-01-01',
                'ext' => ['excerpt' => ['tags' => [$tag->getName()]]],
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $article1 = json_decode($client->getResponse()->getContent(), true);
        // create second article which should not appear in response
        $article2 = $this->post();

        $client->request('GET', '/api/articles?locale=de&tagId=' . $tag->getId());
        $this->assertHttpStatusCode(200, $client->getResponse());
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $result['_embedded']['articles']);
        $this->assertEquals($article1['id'], $result['_embedded']['articles'][0]['id']);
    }

    public function testPostPageTreeRoute()
    {
        $page = $this->createPage('Test Page', '/test-page');

        $routePathData = [
            'page' => [
                'uuid' => $page->getUuid(),
                'path' => $page->getResourceSegment(),
                'webspace' => 'sulu_io',
            ],
            'suffix' => 'test-article',
            'path' => '/test-page/test-article',
        ];

        $response = $this->postPageTreeRoute($routePathData);

        $this->assertEquals('Test Article', $response['title']);
        $this->assertEquals('/test-page/test-article', $response['route']);
        $this->assertEquals($routePathData, $response['routePath']);
    }

    public function testPostPageTreeRouteGenerate()
    {
        $page = $this->createPage('Test Page', '/test-page');

        $routePathData = [
            'page' => [
                'uuid' => $page->getUuid(),
                'path' => $page->getResourceSegment(),
                'webspace' => 'sulu_io',
            ],
            'suffix' => 'articles/test-article',
            'path' => '/test-page/articles/test-article',
        ];

        $response = $this->postPageTreeRoute(['page' => $routePathData['page']]);

        $this->assertEquals('Test Article', $response['title']);
        $this->assertEquals('/test-page/articles/test-article', $response['route']);
        $this->assertEquals($routePathData, $response['routePath']);
    }

    public function testPostPageTreeRouteGeneratePublishPage()
    {
        $page = $this->createPage('Test Page', '/test-page');

        $routePathData = [
            'page' => [
                'uuid' => $page->getUuid(),
                'path' => $page->getResourceSegment(),
            ],
            'suffix' => 'articles/test-article',
            'path' => '/test-page/articles/test-article',
        ];

        $article = $this->postPageTreeRoute(['page' => $routePathData['page']]);

        $page->setResourceSegment('/test-page-2');

        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $documentManager->persist($page, 'de');
        $documentManager->publish($page, 'de');
        $documentManager->flush();
        $documentManager->clear();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles/' . $article['id'] . '?locale=de');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Test Article', $response['title']);
        $this->assertEquals('/test-page-2/articles/test-article', $response['route']);
        $this->assertEquals(
            [
                'page' => [
                    'uuid' => $page->getUuid(),
                    'path' => $page->getResourceSegment(),
                    'webspace' => 'sulu_io',
                ],
                'suffix' => 'articles/test-article',
                'path' => '/test-page-2/articles/test-article',
            ],
            $response['routePath']
        );
    }

    public function testPostPageTreeRouteGenerateMovePage()
    {
        $page1 = $this->createPage('Page 1', '/page-1');
        $page2 = $this->createPage('Page 2', '/page-2');

        $routePathData = [
            'page' => [
                'uuid' => $page1->getUuid(),
                'path' => $page1->getResourceSegment(),
            ],
            'suffix' => 'test-article',
            'path' => '/test-page/articles/test-article',
        ];

        $article = $this->postPageTreeRoute(['page' => $routePathData['page']]);

        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $documentManager->move($page1, $page2->getUuid());
        $documentManager->flush();
        $documentManager->clear();

        $page1 = $documentManager->find($page1->getUuid(), 'de');

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles/' . $article['id'] . '?locale=de');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Test Article', $response['title']);
        $this->assertEquals('/page-2/page-1/articles/test-article', $response['route']);
        $this->assertEquals(
            [
                'page' => [
                    'uuid' => $page1->getUuid(),
                    'path' => $page1->getResourceSegment(),
                    'webspace' => 'sulu_io',
                ],
                'suffix' => 'articles/test-article',
                'path' => '/page-2/page-1/articles/test-article',
            ],
            $response['routePath']
        );
    }

    public function testPostPageTreeRouteGenerateRemovePage()
    {
        $page = $this->createPage('Test Page', '/test-page');

        $routePathData = [
            'page' => [
                'uuid' => $page->getUuid(),
                'path' => $page->getResourceSegment(),
                'webspace' => 'sulu_io',
            ],
            'suffix' => 'articles/test-article',
            'path' => '/test-page/articles/test-article',
        ];

        $article = $this->postPageTreeRoute($routePathData);

        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $documentManager->remove($page);
        $documentManager->flush();
        $documentManager->clear();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/articles/' . $article['id'] . '?locale=de');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Test Article', $response['title']);
        $this->assertEquals('/test-page/articles/test-article', $response['route']);
        $this->assertEquals(
            [
                'page' => null,
                'suffix' => 'articles/test-article',
                'path' => '/test-page/articles/test-article',
            ],
            $response['routePath']
        );
    }

    public function testOrderPages()
    {
        $article = $this->post();
        $pages = [
            $this->postPage($article, 'Page 1'),
            $this->postPage($article, 'Page 2'),
            $this->postPage($article, 'Page 3'),
        ];
        $expectedPages = [$pages[1]['id'], $pages[2]['id'], $pages[0]['id']];

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles/' . $article['id'] . '?action=order&locale=de',
            ['pages' => $expectedPages]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $responsePages = $response['_embedded']['pages'];
        for ($i = 0; $i < count($expectedPages); ++$i) {
            $this->assertEquals($expectedPages[$i], $responsePages[$i]['id']);
            $this->assertEquals($i + 2, $responsePages[$i]['pageNumber']);
        }
    }

    private function postPageTreeRoute($routePathData, $title = 'Test Article')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de',
            [
                'title' => $title,
                'template' => 'page_tree_route',
                'routePath' => $routePathData,
                'authored' => '2016-01-01',
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        return json_decode($client->getResponse()->getContent(), true);
    }

    /**
     * Create a media.
     *
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

    /**
     * Create a category.
     *
     * @return Category
     */
    private function createCategory()
    {
        $entityManager = $this->getEntityManager();

        $category = new Category();
        $category->setDefaultLocale('de');
        $entityManager->persist($category);
        $entityManager->flush();

        return $category;
    }

    /**
     * Create a tag.
     *
     * @return Tag
     */
    private function createTag()
    {
        $entityManager = $this->getEntityManager();

        $tag = new Tag();
        $tag->setName('Test');
        $entityManager->persist($tag);
        $entityManager->flush();

        return $tag;
    }

    /**
     * Create a contact.
     *
     * @return Contact
     */
    private function createContact()
    {
        $entityManager = $this->getEntityManager();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $entityManager->persist($contact);
        $entityManager->flush();

        return $contact;
    }

    /**
     * Create a new page.
     *
     * @param string $title
     * @param string $resourceSegment
     * @param string $locale
     *
     * @return PageDocument
     */
    private function createPage($title, $resourceSegment, $locale = 'de')
    {
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $sessionManager = $this->getContainer()->get('sulu.phpcr.session');

        $page = $documentManager->create('page');

        $uuidReflection = new \ReflectionProperty(PageDocument::class, 'uuid');
        $uuidReflection->setAccessible(true);
        $uuidReflection->setValue($page, Uuid::uuid4()->toString());

        $page->setTitle($title);
        $page->setStructureType('default');
        $page->setParent($documentManager->find($sessionManager->getContentPath('sulu_io')));
        $page->setResourceSegment($resourceSegment);

        $documentManager->persist($page, $locale);
        $documentManager->publish($page, $locale);
        $documentManager->flush();

        return $page;
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

    private function findViewDocument($uuid, $locale)
    {
        /** @var Manager $manager */
        $manager = $this->getContainer()->get('es.manager.default');

        return $manager->find(ArticleViewDocument::class, $this->getViewDocumentId($uuid, $locale));
    }

    private function getRoute($title)
    {
        return '/articles/' . Urlizer::urlize($title);
    }
}
