<?php

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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twig\Environment;

/**
 * Handles articles.
 */
class WebsiteArticleController extends AbstractController
{
    /**
     * @var TemplateAttributeResolverInterface
     */
    private $templateAttributeResolver;

    /**
     * @var ArticleContentResolverInterface
     */
    private $articleContentResolver;

    public function __construct(
        TemplateAttributeResolverInterface $templateAttributeResolver,
        ArticleContentResolverInterface $articleContentResolver
    ) {
        $this->templateAttributeResolver = $templateAttributeResolver;
        $this->articleContentResolver = $articleContentResolver;
    }

    /**
     * Article index action.
     *
     * @param string $view
     * @param int $pageNumber
     *
     * @return Response
     */
    public function indexAction(Request $request, ArticleInterface $object, $view, $pageNumber = 1, $preview = false, $partial = false)
    {
        return $this->renderArticle($request, $object, $view, $pageNumber, $preview, $partial);
    }

    /**
     * Render article with given view.
     *
     * @param string $view
     * @param int $pageNumber
     * @param array $attributes
     *
     * @return Response
     */
    protected function renderArticle(Request $request, ArticleInterface $object, $view, $pageNumber, $preview, $partial, $attributes = [])
    {
        $object = $this->normalizeArticle($object);

        $requestFormat = $request->getRequestFormat();
        $viewTemplate = $view . '.' . $requestFormat . '.twig';

        $content = $this->resolveArticle($object, $pageNumber);

        $data = $this->templateAttributeResolver->resolve(array_merge($content, $attributes));

        try {
            if ($partial) {
                $response = $this->createResponse($request);
                $response->setContent(
                    $this->renderBlock(
                        $viewTemplate,
                        'content',
                        $data
                    )
                );

                return $response;
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
     *
     * @return ArticleDocument
     */
    protected function normalizeArticle(ArticleInterface $object)
    {
        if ($object instanceof ArticlePageDocument) {
            return $object->getParent();
        }

        return $object;
    }

    /**
     * Serialize given article with page-number.
     *
     * @param int $pageNumber
     *
     * @return array
     */
    protected function resolveArticle(ArticleInterface $object, $pageNumber)
    {
        return $this->articleContentResolver->resolve($object, $pageNumber);
    }

    /**
     * Create response.
     *
     * @return Response
     */
    private function createResponse(Request $request)
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

        // we need to set the content type ourselves here
        // else symfony will use the accept header of the client and the page could be cached with false content-type
        // see following symfony issue: https://github.com/symfony/symfony/issues/35694
        $mimeType = $request->getMimeType($request->getRequestFormat());

        if ($mimeType) {
            $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }

    protected function renderBlock($template, $block, $attributes = [])
    {
        /** @var Environment $twig */
        $twig = $this->container->get('twig');
        $attributes = $twig->mergeGlobals($attributes);

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

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'twig' => Environment::class,
        ]);
    }
}
