<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Content;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\PageBundle\Content\Types\SegmentSelect;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ArticleDataProviderTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

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

    public function testResolveDataItems()
    {
        $item = $this->createArticle();

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveDataItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io']);

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertCount(1, $result->getItems());
        $this->assertEquals($item['id'], $result->getItems()[0]->getId());
    }

    public function testResolveDataItemsTypeParam()
    {
        $item1 = $this->createArticle();
        $item2 = $this->createArticle('Test', 'simple');

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        // get all articles with type video
        $result = $dataProvider->resolveDataItems(
            [],
            ['types' => new PropertyParameter('types', 'video')],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertCount(1, $result->getItems());
        $this->assertEquals($item2['id'], $result->getItems()[0]->getId());
    }

    public function testResolveDataItemsTypeParamMultiple()
    {
        $item1 = $this->createArticle();
        $item2 = $this->createArticle('Test', 'simple');

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        // get all articles with type video or blog
        $result = $dataProvider->resolveDataItems(
            [],
            ['types' => new PropertyParameter('types', 'video,blog')],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertCount(2, $result->getItems());
        $this->assertContains(
            $item1['id'],
            [$result->getItems()[0]->getId(), $result->getItems()[1]->getId()]
        );
        $this->assertContains(
            $item2['id'],
            [$result->getItems()[0]->getId(), $result->getItems()[1]->getId()]
        );
    }

    public function testResolveDataItemsTypeParamWrong()
    {
        $item1 = $this->createArticle();
        $item2 = $this->createArticle('Test', 'simple');

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        // get all articles with type other
        $result = $dataProvider->resolveDataItems(
            [],
            ['types' => new PropertyParameter('types', 'other')],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );
        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertCount(0, $result->getItems());
    }

    public function testResolveDataItemsStructureTypeParam()
    {
        $item1 = $this->createArticle();
        $item2 = $this->createArticle('Test', 'simple');

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        // get all articles with structureType simple
        $result = $dataProvider->resolveDataItems(
            [],
            ['structureTypes' => new PropertyParameter('structureTypes', 'simple')],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertCount(1, $result->getItems());
        $this->assertEquals($item2['id'], $result->getItems()[0]->getId());
    }

    public function testResolveDataItemsStructureTypeParamMultiple()
    {
        $item1 = $this->createArticle('Test #1', 'default');
        $item2 = $this->createArticle('Test #2', 'simple');
        $item3 = $this->createArticle('Test no match', 'default_fallback');

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        // get all articles with structureType default or simple
        $result = $dataProvider->resolveDataItems(
            [],
            ['structureTypes' => new PropertyParameter('structureTypes', 'default,simple')],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertCount(2, $result->getItems());
        $this->assertContains(
            $item1['id'],
            [$result->getItems()[0]->getId(), $result->getItems()[1]->getId()]
        );
        $this->assertContains(
            $item2['id'],
            [$result->getItems()[0]->getId(), $result->getItems()[1]->getId()]
        );
    }

    public function testResolveDataItemsStructureTypeParamWrong()
    {
        $item1 = $this->createArticle();
        $item2 = $this->createArticle('Test', 'simple');

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        // get all articles with structureType default_fallback
        $result = $dataProvider->resolveDataItems(
            [],
            ['structureTypes' => new PropertyParameter('structureTypes', 'default_fallback')],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );
        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertCount(0, $result->getItems());
    }

    public function testResolveDataItemsPagination()
    {
        $items = [
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
        ];

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveDataItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io'], null, 1, 2);
        $this->assertCount(2, $result->getItems());
        $this->assertTrue($result->getHasNextPage());
        $result = $dataProvider->resolveDataItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io'], null, 2, 2);
        $this->assertCount(1, $result->getItems());
        $this->assertFalse($result->getHasNextPage());
    }

    public function testResolveResourceItemsPaginationWithPageSizeSmallerThanDefaultLimit()
    {
        $items = [
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
        ];

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $reflectionClass = new \ReflectionClass($dataProvider);
        $reflectionProperty = $reflectionClass->getProperty('defaultLimit');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($dataProvider, 3);

        $pageSize = 20; // need to be bigger as default limit and should have no effect then

        $result = $dataProvider->resolveResourceItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io'], null, 1, $pageSize);
        $this->assertCount(3, $result->getItems());
        $this->assertTrue($result->getHasNextPage());

        $result = $dataProvider->resolveResourceItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io'], null, 2, $pageSize);
        $this->assertCount(1, $result->getItems());
        $this->assertFalse($result->getHasNextPage());
    }

    public function testResolveResourceItemsPaginationWithLimit()
    {
        $items = [
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
        ];

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveResourceItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io'], 3, 1, 2);
        $this->assertCount(2, $result->getItems());
        $this->assertTrue($result->getHasNextPage());

        $result = $dataProvider->resolveResourceItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io'], 3, 2, 2);
        $this->assertCount(1, $result->getItems());
        $this->assertFalse($result->getHasNextPage());
    }

    public function testResolveResourceItemsWithSegments()
    {
        if (!class_exists(SegmentSelect::class)) {
            $this->markTestSkipped('Segments did not exist in Sulu <2.2.');
        }

        $items = [
            $this->createArticle('Test Article 1', 'default', 'test', null, ['test' => 's']),
            $this->createArticle('Test Article 2', 'default', 'test', null, ['test' => 'w']),
            $this->createArticle('Test Article 3', 'default', 'test'),
        ];

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveResourceItems(
            ['segmentKey' => 's'],
            [],
            ['locale' => 'de', 'webspaceKey' => 'test']
        );
        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('Test Article 1', $items[0]->getTitle());
        $this->assertEquals('Test Article 3', $items[1]->getTitle());

        $result = $dataProvider->resolveResourceItems(
            ['segmentKey' => 'w'],
            [],
            ['locale' => 'de', 'webspaceKey' => 'test']
        );
        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('Test Article 2', $items[0]->getTitle());
        $this->assertEquals('Test Article 3', $items[1]->getTitle());
    }

    public function testResolveResourceItemsPagination()
    {
        $items = [
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
        ];

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveResourceItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io'], null, 1, 2);
        $this->assertCount(2, $result->getItems());
        $this->assertTrue($result->getHasNextPage());
        $result = $dataProvider->resolveResourceItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io'], null, 2, 2);
        $this->assertCount(1, $result->getItems());
        $this->assertFalse($result->getHasNextPage());
    }

    public function testResolveDataItemsPaginationWithLimit()
    {
        $items = [
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
        ];

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveDataItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io'], 3, 1, 2);
        $this->assertCount(2, $result->getItems());
        $this->assertTrue($result->getHasNextPage());
        $result = $dataProvider->resolveDataItems([], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io'], 3, 2, 2);
        $this->assertCount(1, $result->getItems());
        $this->assertFalse($result->getHasNextPage());
    }

    public function testResolveDataItemsPaginationWithExcluded()
    {
        $items = [
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
        ];

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveDataItems(['excluded' => [$items[0]['id']]], [], ['locale' => 'de', 'webspaceKey' => 'sulu_io']);

        $this->assertCount(3, $result->getItems());
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals($items[$i + 1]['id'], $result->getItems()[$i]->getId());
        }
    }

    public function testResolveDataItemsPaginationWithReferenceStore()
    {
        $items = [
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
        ];

        $referenceStore = $this->getContainer()->get('sulu_article.reference_store.article');
        $referenceStore->add($items[0]['id']);

        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveDataItems(
            [],
            ['exclude_duplicates' => new PropertyParameter('exclude_duplicates', true)],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $this->assertCount(3, $result->getItems());
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals($items[$i + 1]['id'], $result->getItems()[$i]->getId());
        }
    }

    public function testResolveDataItemsPaginationExludeDuplicatedFalse()
    {
        $items = [
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
        ];

        $referenceStore = $this->getContainer()->get('sulu_article.reference_store.article');
        $referenceStore->add($items[0]['id']);

        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveDataItems(
            [],
            ['exclude_duplicates' => new PropertyParameter('exclude_duplicates', false)],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $this->assertCount(4, $result->getItems());
        for ($i = 0; $i < 4; ++$i) {
            $this->assertEquals($items[$i]['id'], $result->getItems()[$i]->getId());
        }
    }

    public function testResolveDataItemsPaginationExludeDuplicatedNull()
    {
        $items = [
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
            $this->createArticle(),
        ];

        $referenceStore = $this->getContainer()->get('sulu_article.reference_store.article');
        $referenceStore->add($items[0]['id']);

        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveDataItems(
            [],
            [],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $this->assertCount(4, $result->getItems());
        for ($i = 0; $i < 4; ++$i) {
            $this->assertEquals($items[$i]['id'], $result->getItems()[$i]->getId());
        }
    }

    public function testResolveDataItemsWithCustomWebspaceSettings()
    {
        $items = [
            $this->createArticle('Test-Article 1', 'default', 'test'),
            $this->createArticle('Test-Article 2', 'default', 'test-2', ['sulu_io']),
            $this->createArticle('Test-Article 2', 'default', 'sulu_io'),
            $this->createArticle(),
        ];

        $referenceStore = $this->getContainer()->get('sulu_article.reference_store.article');
        $referenceStore->add($items[0]['id']);

        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveDataItems(
            [],
            [],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $this->assertCount(3, $result->getItems());
    }

    public function testResolveResourceItemsWithIgnoreWebspaces()
    {
        $this->createArticle('Test-Article 1', 'default', 'test', []);
        $this->createArticle('Test-Article 2', 'default', 'test-2', ['sulu_io']);
        $this->createArticle('Test-Article 2', 'default', 'sulu_io', []);
        $this->createArticle();

        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        // push fake request with `sulu_io`
        $this->pushFakeRequest('sulu_io');

        $result = $dataProvider->resolveResourceItems(
            [],
            ['ignoreWebspaces' => new PropertyParameter('ignoreWebspaces', true)],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $items = $result->getItems();
        $this->assertCount(4, $items);
        $this->assertEquals('test', $items[0]->getTargetWebspace());
        $this->assertEquals('sulu_io', $items[1]->getTargetWebspace());
        $this->assertEquals('sulu_io', $items[2]->getTargetWebspace());
        $this->assertEquals('sulu_io', $items[3]->getTargetWebspace());

        $this->popFakeRequest();

        // push fake request with `test`
        $this->pushFakeRequest('test');

        $result = $dataProvider->resolveResourceItems(
            [],
            ['ignoreWebspaces' => new PropertyParameter('ignoreWebspaces', true)],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $items = $result->getItems();
        $this->assertCount(4, $items);
        $this->assertEquals('test', $items[0]->getTargetWebspace());
        $this->assertEquals('test-2', $items[1]->getTargetWebspace());
        $this->assertEquals('sulu_io', $items[2]->getTargetWebspace());
        $this->assertEquals('sulu_io', $items[3]->getTargetWebspace());

        $this->popFakeRequest();
    }

    private function pushFakeRequest($webspaceKey)
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

        /** @var RequestStack $requestStack */
        $requestStack = $this->getContainer()->get('request_stack');
        $requestStack->push($fakeRequest);
    }

    private function popFakeRequest()
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->getContainer()->get('request_stack');
        $requestStack->pop();
    }

    public function testResolveDataItemsWithIgnoreWebspaces()
    {
        $items = [
            $this->createArticle('Test-Article 1', 'default', 'test'),
            $this->createArticle('Test-Article 2', 'default', 'test-2', ['sulu_io']),
            $this->createArticle('Test-Article 2', 'default', 'sulu_io'),
            $this->createArticle(),
        ];

        $referenceStore = $this->getContainer()->get('sulu_article.reference_store.article');
        $referenceStore->add($items[0]['id']);

        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveDataItems(
            [],
            ['ignoreWebspaces' => new PropertyParameter('ignoreWebspaces', true)],
            ['locale' => 'de', 'webspaceKey' => 'sulu_io']
        );

        $this->assertCount(4, $result->getItems());
    }

    private function createArticle(
        $title = 'Test-Article',
        $template = 'default',
        $mainWebspace = null,
        $additionalWebspaces = null,
        $segments = null
    ) {
        $data = [
            'title' => $title,
            'template' => $template,
        ];

        if ($mainWebspace) {
            $data['mainWebspace'] = $mainWebspace;
        }

        if ($additionalWebspaces) {
            $data['additionalWebspaces'] = $additionalWebspaces;
        }

        if ($segments) {
            $data['ext'] = ['excerpt' => ['segments' => $segments]];
        }

        $this->client->request(
            'POST',
            '/api/articles?locale=de&action=publish',
            $data
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        return json_decode($response->getContent(), true);
    }
}
