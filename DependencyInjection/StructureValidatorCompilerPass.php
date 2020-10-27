<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\DependencyInjection;

use Sulu\Component\Content\Metadata\Factory\Exception\StructureTypeNotFoundException;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Replaces class and add argument for replaced service "es.result_converter".
 */
class StructureValidatorCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $types = ['article', 'article_page'];
        $defaultTypes = $container->getParameter('sulu.content.structure.default_types');
        /** @var StructureMetadataFactory $structureFactory */
        $structureFactory = $container->get('sulu_page.structure.factory');

        foreach ($types as $type) {
            $defaultType = $defaultTypes[$type] ?? null;

            if (!$defaultType) {
                continue;
            }

            try {
                $structureFactory->getStructureMetadata($type, $defaultType);
            } catch (StructureTypeNotFoundException $exception) {
                throw new DefaultArticleTypeNotFoundException($type, $defaultType, $exception);
            }
        }
    }
}
