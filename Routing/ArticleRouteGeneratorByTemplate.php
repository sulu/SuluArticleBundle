<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Routing;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Exception\RouteSchemaNotFoundException;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generate route for articles by type.
 */
class ArticleRouteGeneratorByTemplate implements RouteGeneratorInterface
{
    use StructureTagTrait;

    /**
     * @var RouteGeneratorInterface
     */
    private $routeGenerator;

    public function __construct(RouteGeneratorInterface $routeGenerator)
    {
        $this->routeGenerator = $routeGenerator;
    }

    /**
     * {@inheritdoc}
     *
     * @param ArticleDocument $entity
     */
    public function generate($entity, array $options)
    {
        $template = $entity->getStructureType();

        if (!array_key_exists($template, $options)) {
            throw new RouteSchemaNotFoundException($template, array_keys($options));
        }

        return $this->routeGenerator->generate($entity, ['route_schema' => $options[$template]]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsResolver(array $options)
    {
        return (new OptionsResolver())->setDefined(array_keys($options));
    }
}
