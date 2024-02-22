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

use Sulu\Bundle\ArticleBundle\DependencyInjection\Configuration;
use Sulu\Bundle\ArticleBundle\DependencyInjection\ConverterCompilerPass;
use Sulu\Bundle\ArticleBundle\DependencyInjection\RouteEnhancerCompilerPass;
use Sulu\Bundle\ArticleBundle\DependencyInjection\StructureValidatorCompilerPass;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleInterface;
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

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass($this);
        $container->addCompilerPass(new StructureValidatorCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }

    public function process(ContainerBuilder $container): void
    {
        $storage = $container->getParameter('sulu_article.article_storage');

        $compilerPasses = [];

        if (Configuration::ARTICLE_STORAGE_PHPCR === $storage) {
            $compilerPasses = $this->getPHPCRStorageCompilerPasses($container);
        } elseif (Configuration::ARTICLE_STORAGE_EXPERIMENTAL === $storage) {
            $compilerPasses = $this->getExperimentalStorageCompilerPasses($container);
        }

        foreach ($compilerPasses as $compilerPass) {
            $compilerPass->process($container);
        }
    }

    /**
     * @return CompilerPassInterface[]
     */
    private function getExperimentalStorageCompilerPasses(ContainerBuilder $container): array
    {
        return [
            new ResolveTargetEntitiesPass([
                ArticleInterface::class => 'sulu.model.article.class',
                ArticleDimensionContentInterface::class => 'sulu.model.article_content.class',
            ]),
        ];
    }

    /**
     * @return compilerPassInterface[]
     *
     * Can be removed when phpcr storage is removed
     */
    private function getPHPCRStorageCompilerPasses(ContainerBuilder $container): array
    {
        return [
            new RouteEnhancerCompilerPass(),
            new ConverterCompilerPass(),
        ];
    }
}
