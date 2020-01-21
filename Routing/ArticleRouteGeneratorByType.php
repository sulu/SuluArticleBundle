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

namespace Sulu\Bundle\ArticleBundle\Routing;

use Sulu\Bundle\ArticleBundle\Exception\RouteSchemaNotFoundException;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generate route for articles by type.
 */
class ArticleRouteGeneratorByType implements RouteGeneratorInterface
{
    use StructureTagTrait;

    /**
     * @var RouteGeneratorInterface
     */
    private $routeGenerator;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    public function __construct(
        RouteGeneratorInterface $routeGenerator,
        StructureMetadataFactoryInterface $structureMetadataFactory
    ) {
        $this->routeGenerator = $routeGenerator;
        $this->structureMetadataFactory = $structureMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($entity, array $options)
    {
        $type = $this->getType(
            $this->structureMetadataFactory->getStructureMetadata('article', $entity->getStructureType())
        );

        if (!array_key_exists($type, $options)) {
            throw new RouteSchemaNotFoundException($type, array_keys($options));
        }

        return $this->routeGenerator->generate($entity, ['route_schema' => $options[$type]]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsResolver(array $options)
    {
        return (new OptionsResolver())->setDefined(array_keys($options));
    }
}
