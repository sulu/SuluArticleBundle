<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Teaser;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ArticleTeaserProviderTest extends SuluTestCase
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

    public function testFind()
    {
        $item1 = $this->createArticle('1');
        $item2 = $this->createArticle('2');

        /** @var TeaserProviderInterface $provider */
        $provider = $this->getContainer()->get('sulu_article.teaser.provider');

        $result = $provider->find([$item1['id'], $item2['id']], 'de');

        $this->assertCount(2, $result);
        $this->assertEquals($item1['id'], $result[0]->getId());
        $this->assertEquals($item1['title'], $result[0]->getTitle());
        $this->assertEquals($item2['id'], $result[1]->getId());
        $this->assertEquals($item2['title'], $result[1]->getTitle());
    }

    public function testFindWithFallback()
    {
        $item = $this->createArticle(
            '1',
            'default_fallback',
            ['medias' => ['ids' => [5, 4, 3], 'display_options' => 'top'], 'description' => 'Sulu is awesome']
        );

        /** @var TeaserProviderInterface $provider */
        $provider = $this->getContainer()->get('sulu_article.teaser.provider');

        $result = $provider->find([$item['id']], 'de');

        $this->assertCount(1, $result);
        $this->assertEquals($item['id'], $result[0]->getId());
        $this->assertEquals($item['title'], $result[0]->getTitle());
        $this->assertEquals($item['description'], $result[0]->getDescription());
        $this->assertEquals(5, $result[0]->getMediaId());
    }

    private function createArticle($title = 'Test-Article', $template = 'default', $data = [])
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
