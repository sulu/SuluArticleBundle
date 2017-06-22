<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Routing;

use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Generator\TokenProviderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generate route for article-pages.
 */
class ArticlePageRouteGenerator implements RouteGeneratorInterface
{
    use StructureTagTrait;

    /**
     * @var RouteGeneratorInterface
     */
    private $routeGenerator;

    /**
     * @var TokenProviderInterface
     */
    private $tokenProvider;

    /**
     * @param RouteGeneratorInterface $routeGenerator
     * @param TokenProviderInterface $tokenProvider
     */
    public function __construct(RouteGeneratorInterface $routeGenerator, TokenProviderInterface $tokenProvider)
    {
        $this->routeGenerator = $routeGenerator;
        $this->tokenProvider = $tokenProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($entity, array $options)
    {
        $parent = $options['parent'];

        $tokens = [];
        preg_match_all('/{(.*?)}/', $parent, $matches);
        $tokenNames = $matches[1];

        foreach ($tokenNames as $name) {
            $tokenName = '{' . $name . '}';
            $tokenValue = $this->tokenProvider->provide($entity, $name);

            $tokens[$tokenName] = $tokenValue;
        }

        $parentPath = strtr($parent, $tokens);

        return $this->routeGenerator->generate($entity, ['route_schema' => $parentPath . $options['route_schema']]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsResolver(array $options)
    {
        return (new OptionsResolver())->setRequired(['route_schema', 'parent']);
    }
}
