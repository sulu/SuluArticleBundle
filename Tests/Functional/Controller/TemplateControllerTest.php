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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TemplateControllerTest extends SuluTestCase
{
    public function testGet()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/articles/templates?type=blog');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']);
        $this->assertContains(
            [
                'internal' => false,
                'template' => 'default_with_route',
                'title' => 'Default_with_route',
            ],
            $response['_embedded']
        );

        $this->assertContains(
            [
                'internal' => false,
                'template' => 'default',
                'title' => 'Default',
            ],
            $response['_embedded']
        );

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/articles/templates?type=video');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']);
        $this->assertEquals('simple', $response['_embedded'][0]['template']);
        $this->assertEquals('Simple', $response['_embedded'][0]['title']);
    }
}
