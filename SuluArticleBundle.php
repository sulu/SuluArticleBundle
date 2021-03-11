<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle;

use Sulu\Bundle\ArticleBundle\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\ArticleBundle\DependencyInjection\Configuration;
use Sulu\Bundle\ArticleBundle\DependencyInjection\ConverterCompilerPass;
use Sulu\Bundle\ArticleBundle\DependencyInjection\RouteEnhancerCompilerPass;
use Sulu\Bundle\ArticleBundle\DependencyInjection\StructureValidatorCompilerPass;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\Compiler\ResolveTargetEntitiesPass;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entry-point for article-bundle.
 */
class SuluArticleBundle extends Bundle implements CompilerPassInterface
{
    use PersistenceBundleTrait;

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass($this);
        $container->addCompilerPass(new StructureValidatorCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }

    public function process(ContainerBuilder $container): void
    {
        $interfaces = [];

        if (Configuration::ARTICLE_STORAGE_EXPERIMENTAL === $container->getParameter('sulu_article.article_storage')) {
            $interfaces = \array_merge($interfaces, [
                ArticleInterface::class => 'sulu.model.article.class',
            ]);
        }

        $compilerPasses = [];
        if (0 < \count($interfaces)) {
            $compilerPasses[] = new ResolveTargetEntitiesPass($interfaces);
        }

        if (Configuration::ARTICLE_STORAGE_PHPCR === $container->getParameter('sulu_article.article_storage')) {
            // can be removed when phpcr storage is removed
            $compilerPasses[] = new RouteEnhancerCompilerPass();
            $compilerPasses[] = new ConverterCompilerPass();
        }

        foreach ($compilerPasses as $compilerPass) {
            $compilerPass->process($container);
        }
    }
}
