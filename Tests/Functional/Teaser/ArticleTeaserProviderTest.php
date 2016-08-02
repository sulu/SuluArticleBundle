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
        $item1 = $this->createArticle();
        $item2 = $this->createArticle();

        /** @var TeaserProviderInterface $provider */
        $provider = $this->getContainer()->get('sulu_article.teaser.provider');

        $result = $provider->find([$item2['id'], $item1['id']], 'de');

        $this->assertCount(2, $result);
        $this->assertEquals($item2['id'], $result[0]->getId());
        $this->assertEquals($item2['title'], $result[0]->getTitle());
        $this->assertEquals($item1['id'], $result[1]->getId());
        $this->assertEquals($item2['title'], $result[1]->getTitle());
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
