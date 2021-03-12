<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Application;

use ONGR\ElasticsearchBundle\ONGRElasticsearchBundle;
use Sulu\Bundle\ArticleBundle\SuluArticleBundle;
use Sulu\Bundle\ArticleBundle\Tests\Application\Testing\ArticleBundleKernelBrowser;
use Sulu\Bundle\ArticleBundle\Tests\TestExtendBundle\TestExtendBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * AppKernel for functional tests.
 */
class Kernel extends SuluTestKernel implements CompilerPassInterface
{
    /**
     * @var string|null
     */
    private $config = 'phpcr_storage';

    public function __construct(string $environment, bool $debug, string $suluContext = SuluKernel::CONTEXT_ADMIN)
    {
        $environmentParts = explode('_', $environment, 2);
        $environment = $environmentParts[0];
        $this->config = $environmentParts[1] ?? $this->config;

        parent::__construct($environment, $debug, $suluContext);
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = parent::registerBundles();
        $bundles[] = new SuluArticleBundle();

        if ('phpcr_storage' === $this->config) {
            $bundles[] = new ONGRElasticsearchBundle();
        }

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

        if ('jackrabbit' === getenv('PHPCR_TRANSPORT')) {
            $loader->load(__DIR__ . '/config/versioning.yml');
        }

        $loader->load(__DIR__ . '/config/config.yml');
        $loader->load(__DIR__ . '/config/config_' . $this->config . '.yml');

        $type = 'default';
        if (getenv('ARTICLE_TEST_CASE')) {
            $type = getenv('ARTICLE_TEST_CASE');
        }

        $loader->load(__DIR__ . '/config/config_' . $type . '.yml');
    }

    public function process(ContainerBuilder $container)
    {
        // Make some services which were inlined in optimization
        $container->getDefinition('sulu_article.content.page_tree_data_provider')
            ->setPublic(true);

        $container->getDefinition('sulu_article.elastic_search.article_indexer')
            ->setPublic(true);

        // Will be removed, as soon as the min-requirement of sulu/sulu is high enough for the `SuluKernelBrowser` to be always available.
        if ($container->hasDefinition('test.client')) {
            $definition = $container->getDefinition('test.client');

            if (\Sulu\Bundle\TestBundle\Kernel\SuluKernelBrowser::class !== $definition->getClass()) {
                $definition->setClass(ArticleBundleKernelBrowser::class);
            }
        }
    }

    protected function getKernelParameters()
    {
        $parameters = parent::getKernelParameters();

        $gedmoReflection = new \ReflectionClass(\Gedmo\Exception::class);
        $parameters['gedmo_directory'] = \dirname($gedmoReflection->getFileName());

        return $parameters;
    }
}
