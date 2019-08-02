<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Application;

use ONGR\ElasticsearchBundle\ONGRElasticsearchBundle;
use Sulu\Bundle\ArticleBundle\SuluArticleBundle;
use Sulu\Bundle\ArticleBundle\Tests\TestExtendBundle\TestExtendBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * AppKernel for functional tests.
 */
class Kernel extends SuluTestKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = parent::registerBundles();
        $bundles[] = new SuluArticleBundle();
        $bundles[] = new ONGRElasticsearchBundle();

        if ('extend' === getenv('ARTICLE_TEST_CASE')) {
            $bundles[] = new TestExtendBundle();
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(__DIR__ . '/config/config.php');

        if ('jackrabbit' === getenv('PHPCR_TRANSPORT')) {
            $loader->load(__DIR__ . '/config/versioning.yml');
        }

        $loader->load(__DIR__ . '/config/config.yml');
        $type = 'default';
        if (getenv('ARTICLE_TEST_CASE')) {
            $type = getenv('ARTICLE_TEST_CASE');
        }

        $loader->load(__DIR__ . '/config/config_' . $type . '.yml');
    }
}
