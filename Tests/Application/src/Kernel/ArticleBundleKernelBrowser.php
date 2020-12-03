<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Application\Kernel;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class ArticleBundleKernelBrowser extends KernelBrowser
{
    public function jsonRequest(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
        bool $changeHistory = true
    ): Crawler {
        return $this->request($method, $uri, $parameters, [], $server, null, $changeHistory);
    }
}
