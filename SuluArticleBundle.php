<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle;

use Sulu\Bundle\ArticleBundle\DependencyInjection\ConverterCompilerPass;
use Sulu\Bundle\ArticleBundle\DependencyInjection\PageTreeCompilerPass;
use Sulu\Bundle\ArticleBundle\DependencyInjection\RouteEnhancerCompilerPass;
use Sulu\Bundle\ArticleBundle\DependencyInjection\StructureValidatorCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entry-point for article-bundle.
 */
class SuluArticleBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PageTreeCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1024);
        $container->addCompilerPass(new ConverterCompilerPass());
        $container->addCompilerPass(new StructureValidatorCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new RouteEnhancerCompilerPass());
    }
}
