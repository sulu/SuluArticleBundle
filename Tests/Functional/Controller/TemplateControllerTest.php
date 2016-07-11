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
        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']);
        $this->assertEquals('default', $response['_embedded'][0]['template']);
        $this->assertEquals('Default', $response['_embedded'][0]['title']);

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
