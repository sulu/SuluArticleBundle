<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Content;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;

class ArticleDataProviderTest extends SuluTestCase
{
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
    }

    public function testResolveDataItems()
    {
        $item = $this->createArticle();

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->getContainer()->get('sulu_article.content.data_provider');

        $result = $dataProvider->resolveDataItems([], [], ['locale' => 'de']);

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
            ['locale' => 'de']
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
            ['locale' => 'de']
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
            ['locale' => 'de']
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

        $result = $dataProvider->resolveDataItems([], [], ['locale' => 'de'], null, 1, 2);
        $this->assertCount(2, $result->getItems());
        $this->assertTrue($result->getHasNextPage());
        $result = $dataProvider->resolveDataItems([], [], ['locale' => 'de'], null, 2, 2);
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

        $result = $dataProvider->resolveResourceItems([], [], ['locale' => 'de'], 3, 1, 2);
        $this->assertCount(2, $result->getItems());
        $this->assertTrue($result->getHasNextPage());
        $result = $dataProvider->resolveResourceItems([], [], ['locale' => 'de'], 3, 2, 2);
        $this->assertCount(1, $result->getItems());
        $this->assertFalse($result->getHasNextPage());
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

        $result = $dataProvider->resolveResourceItems([], [], ['locale' => 'de'], null, 1, 2);
        $this->assertCount(2, $result->getItems());
        $this->assertTrue($result->getHasNextPage());
        $result = $dataProvider->resolveResourceItems([], [], ['locale' => 'de'], null, 2, 2);
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

        $result = $dataProvider->resolveDataItems([], [], ['locale' => 'de'], 3, 1, 2);
        $this->assertCount(2, $result->getItems());
        $this->assertTrue($result->getHasNextPage());
        $result = $dataProvider->resolveDataItems([], [], ['locale' => 'de'], 3, 2, 2);
        $this->assertCount(1, $result->getItems());
        $this->assertFalse($result->getHasNextPage());
    }

    private function createArticle($title = 'Test-Article', $template = 'default')
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/articles?locale=de&action=publish',
            ['title' => $title, 'template' => $template]
        );

        return json_decode($client->getResponse()->getContent(), true);
    }
}
