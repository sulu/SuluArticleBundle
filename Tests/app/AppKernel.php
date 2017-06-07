<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use ONGR\ElasticsearchBundle\ONGRElasticsearchBundle;
use Sulu\Bundle\ArticleBundle\SuluArticleBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * AppKernel for functional tests.
 */
class AppKernel extends SuluTestKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return array_merge(parent::registerBundles(), [new SuluArticleBundle(), new ONGRElasticsearchBundle()]);
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        parent::registerContainerConfiguration($loader);

        if (getenv('SYMFONY__PHPCR__TRANSPORT') === 'jackrabbit') {
            $loader->load(__DIR__ . '/config/versioning.yml');
        }

        $esVersion = getenv('ES_VERSION');
        if (version_compare($esVersion, '2.2', '>=') &&
            version_compare($esVersion, '5.0', '<')) {
            $loader->load(__DIR__ . '/config/config_es2.yml');
        } else {
            $loader->load(__DIR__ . '/config/config.yml');
        }
    }
}
