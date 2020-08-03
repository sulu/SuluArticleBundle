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

class ArticleExportTest extends SuluTestCase
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

    public function testExport(): void
    {
        $article = $this->createArticle('Test-Article', 'default', [
            'mainWebspace' => 'sulu_io',
            'additionalWebspaces' => ['sulu_io_blog'],
            'ext' => [
                'seo' => [
                    'title' => 'Seo Title',
                ],
                'excerpt' => [
                    'title' => 'Excerpt Title',
                ],
            ],
        ]);

        $exporter = $this->getContainer()->get('sulu_article.export.exporter');

        $result = $exporter->export('de');

        $expected = <<<EOT
            <!-- title: text_line -->
            <trans-unit id="(\d+)" resname="title">
                <source>Test-Article<\/source>
                <target><\/target>
            <\/trans-unit>
EOT;
        $this->assertRegExp('/' . $expected . '/', $result);

        $expected = <<<EOT
            <!-- seo-title:  -->
            <trans-unit id="(\d+)" resname="seo-title">
                <source>Seo Title<\/source>
                <target><\/target>
            <\/trans-unit>
EOT;
        $this->assertRegExp('/' . $expected . '/', $result);

        $expected = <<<EOT
            <!-- excerpt-title: text_line -->
            <trans-unit id="(\d+)" resname="excerpt-title">
                <source>Excerpt Title<\/source>
                <target><\/target>
            <\/trans-unit>
EOT;
        $this->assertRegExp('/' . $expected . '/', $result);

        $expected = <<<EOT
            <!-- structureType:  -->
            <trans-unit id="(\d+)" resname="structureType" translate="no">
                <source>default<\/source>
                <target>default<\/target>
            <\/trans-unit>
EOT;
        $this->assertRegExp('/' . $expected . '/', $result);

        $expected = <<<EOT
            <!-- locale:  -->
            <trans-unit id="(\d+)" resname="locale" translate="no">
                <source>de<\/source>
                <target>de<\/target>
            <\/trans-unit>
EOT;
        $this->assertRegExp('/' . $expected . '/', $result);

        $expected = <<<EOT
            <!-- mainWebspace:  -->
            <trans-unit id="(\d+)" resname="mainWebspace" translate="no">
                <source>sulu_io<\/source>
                <target>sulu_io<\/target>
            <\/trans-unit>
EOT;
        $this->assertRegExp('/' . $expected . '/', $result);

        $expected = <<<EOT
            <!-- additionalWebspaces:  -->
            <trans-unit id="(\d+)" resname="additionalWebspaces" translate="no">
                <source><!\[CDATA\[\["sulu_io_blog"\]\]\]><\/source>
                <target><!\[CDATA\[\["sulu_io_blog"\]\]\]><\/target>
            <\/trans-unit>
EOT;
        $this->assertRegExp('/' . $expected . '/', $result);
    }

    private function createArticle($title = 'Test-Article', $template = 'default', $data = []): array
    {
        $this->client->request(
            'POST',
            '/api/articles?locale=de&action=publish',
            array_merge($data, ['title' => $title, 'template' => $template])
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
