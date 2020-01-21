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

namespace Sulu\Bundle\ArticleBundle\Controller;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Resolver\ArticleContentResolverInterface;
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twig\Template;

/**
 * Handles articles.
 */
class WebsiteArticleController extends Controller
{
    /** @var ArticleContentResolverInterface */
    private $articleContentResolver;

    /** @var TemplateAttributeResolverInterface */
    private $templateAttributeResolver;

    public function __construct(
        ArticleContentResolverInterface $articleContentResolver,
        TemplateAttributeResolverInterface $templateAttributeResolver
    ) {
        $this->articleContentResolver = $articleContentResolver;
        $this->templateAttributeResolver = $templateAttributeResolver;
    }

    /**
     * Article index action.
     */
    public function indexAction(
        Request $request,
        ArticleInterface $object,
        string $view,
        int $pageNumber = 1,
        bool $preview = false,
        bool $partial = false
    ): Response {
        return $this->renderArticle($request, $object, $view, $pageNumber, $preview, $partial);
    }

    /**
     * Render article with given view.
     */
    protected function renderArticle(
        Request $request,
        ArticleInterface $object,
        string $view,
        int $pageNumber,
        bool $preview,
        bool $partial,
        array $attributes = []
    ): Response {
        $object = $this->normalizeArticle($object);

        $requestFormat = $request->getRequestFormat();
        $viewTemplate = $view . '.' . $requestFormat . '.twig';

        $content = $this->resolveArticle($object, $pageNumber);

        $data = $this->templateAttributeResolver->resolve(array_merge($content, $attributes));

        try {
            if ($partial) {
                return new Response(
                    $this->renderBlock(
                        $viewTemplate,
                        'content',
                        $data
                    )
                );
            } elseif ($preview) {
                $parameters = [
                    'previewParentTemplate' => $viewTemplate,
                    'previewContentReplacer' => Preview::CONTENT_REPLACER,
                ];

                return $this->render(
                    '@SuluWebsite/Preview/preview.html.twig',
                    array_merge($data, $parameters),
                    $this->createResponse($request)
                );
            } else {
                return $this->render(
                    $viewTemplate,
                    $data,
                    $this->createResponse($request)
                );
            }
        } catch (\InvalidArgumentException $exception) {
            // template not found
            throw new HttpException(406, 'Error encountered when rendering content', $exception);
        }
    }

    /**
     * Returns all the times the article-document.
     * This is necessary because the preview system passes an article-page here.
     */
    protected function normalizeArticle(ArticleInterface $object): ArticleDocument
    {
        if ($object instanceof ArticlePageDocument) {
            return $object->getParent();
        }

        return $object;
    }

    /**
     * Serialize given article with page-number.
     */
    protected function resolveArticle(ArticleInterface $object, int $pageNumber): array
    {
        return $this->articleContentResolver->resolve($object, $pageNumber);
    }

    /**
     * Create response.
     */
    private function createResponse(Request $request): Response
    {
        $response = new Response();
        $cacheLifetime = $request->attributes->get('_cacheLifetime');

        if ($cacheLifetime) {
            $response->setPublic();
            $response->headers->set(
                SuluHttpCache::HEADER_REVERSE_PROXY_TTL,
                $cacheLifetime
            );
            $response->setMaxAge($this->getParameter('sulu_http_cache.cache.max_age'));
            $response->setSharedMaxAge($this->getParameter('sulu_http_cache.cache.shared_max_age'));
        }

        return $response;
    }

    protected function renderBlock(string $template, string $block, array $attributes = []): string
    {
        $twig = $this->get('twig');
        $attributes = $twig->mergeGlobals($attributes);

        /** @var Template $template */
        $template = $twig->loadTemplate($template);

        $level = ob_get_level();
        ob_start();

        try {
            $rendered = $template->renderBlock($block, $attributes);
            ob_end_clean();

            return $rendered;
        } catch (\Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }
}
