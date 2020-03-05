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

use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\Behavior\WebspaceBehavior;
use Sulu\Bundle\ArticleBundle\Document\Resolver\WebspaceResolver;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;

class ArticleRouteEnhancer implements RouteEnhancerInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var WebspaceResolver
     */
    private $webspaceResolver;

    /**
     * @var string
     */
    private $environment;

    /**
     * @param string $environment
     */
    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        WebspaceResolver $webspaceResolver,
        $environment
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->webspaceResolver = $webspaceResolver;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function enhance(array $defaults, Request $request)
    {
        if (!$this->shouldAddCanonicalTag($defaults, $request)) {
            return $defaults;
        }

        $article = $defaults['object'];
        $seo['canonicalUrl'] = $this->webspaceManager->findUrlByResourceLocator(
            $article->getRoutePath(),
            $this->environment,
            $article->getLocale(),
            $this->webspaceResolver->resolveMainWebspace($article)
        );

        return array_merge(
            $defaults,
            ['_seo' => $seo]
        );
    }

    /**
     * Checks if the enhancer should add an canonical tag to the route attributes.
     *
     * @return bool
     */
    private function shouldAddCanonicalTag(array $defaults, Request $request)
    {
        if (!array_key_exists('object', $defaults)) {
            return false;
        }

        $article = $defaults['object'];
        if (!$article instanceof ArticleInterface || !$article instanceof WebspaceBehavior) {
            return false;
        }

        $sulu = $request->get('_sulu');
        if (!$sulu) {
            return false;
        }

        /** @var Webspace $webspace */
        $webspace = $sulu->getAttribute('webspace');
        if (!$webspace) {
            return false;
        }

        $additionalWebspaces = $this->webspaceResolver->resolveAdditionalWebspaces($article);
        if (!$additionalWebspaces || !in_array($webspace->getKey(), $additionalWebspaces)) {
            return false;
        }

        return true;
    }
}
