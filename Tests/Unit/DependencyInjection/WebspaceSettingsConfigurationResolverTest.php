<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ArticleBundle\DependencyInjection\WebspaceSettingsConfigurationResolver;

class WebspaceSettingsConfigurationResolverTest extends TestCase
{
    public function configuration()
    {
        return [
            // Case 1
            [
                [
                    'default' => 'example1',
                ],
                [
                    [],
                ],
                'example1',
                [],
            ],
            // Case 2
            [
                [
                    'default' => 'example1',
                ],
                [
                    ['test1', 'test2'],
                ],
                'example1',
                [],
            ],
            // Case 3
            [
                [
                    'default' => 'example1',
                    'de' => 'test1',
                ],
                [
                    'default' => ['test1', 'test2'],
                    'de' => ['example1'],
                ],
                'test1',
                ['example1'],
            ],
            // Case 4
            [
                [
                    'default' => 'example1',
                    'de' => 'test1',
                ],
                [
                    'default' => ['test1', 'test2'],
                    'de' => ['example1'],
                ],
                'test1',
                ['example1'],
            ],
            // Case 5
            [
                [
                    'default' => 'example1',
                    'en' => 'test1',
                ],
                [
                    'default' => ['test1', 'test2'],
                    'en' => ['example1'],
                ],
                'example1',
                ['test1', 'test2'],
            ],
        ];
    }

    /**
     * @param string $expectedResult
     * @param string[] $expectedResultAdditionalWebspaces
     *
     * @dataProvider configuration
     */
    public function test(
        array $defaultMainWebspaceConfig,
        array $defaultAdditionalWebspaceConfig,
        $expectedResultMainWebspace,
        array $expectedResultAdditionalWebspaces
    ) {
        $web = new WebspaceSettingsConfigurationResolver($defaultMainWebspaceConfig, $defaultAdditionalWebspaceConfig);
        $this->assertEquals($expectedResultMainWebspace, $web->getDefaultMainWebspaceForLocale('de'));
        $this->assertEquals($expectedResultAdditionalWebspaces, $web->getDefaultAdditionalWebspacesForLocale('de'));
    }
}
