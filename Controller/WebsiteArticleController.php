<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Controller;

use JMS\Serializer\SerializationContext;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Handles articles.
 */
class WebsiteArticleController extends Controller
{
    /**
     * Article index action.
     *
     * @param Request $request
     * @param ArticleInterface $object
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
     * @param Request $request
     * @param ArticleInterface $object
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

        $data = $this->get('sulu_website.resolver.template_attribute')->resolve(array_merge($content, $attributes));

        try {
            if ($partial) {
                return new Response(
                    $this->renderBlock(
                        $viewTemplate,
                        'content',
                        $data
                    )
                );
            } else if ($preview) {
                $parameters = [
                    'previewParentTemplate' => $viewTemplate,
                    'previewContentReplacer' => Preview::CONTENT_REPLACER,
                ];

                return $this->render(
                    'SuluWebsiteBundle:Preview:preview.html.twig',
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
     * @param ArticleInterface $object
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
     * @param ArticleInterface $object
     * @param int $pageNumber
     *
     * @return array
     */
    protected function resolveArticle(ArticleInterface $object, $pageNumber)
    {
        return $this->get('sulu_article.article_content_resolver')->resolve($object, $pageNumber);
    }

    /**
     * Create response.
     *
     * @param Request $request
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

        return $response;
    }

    protected function renderBlock($template, $block, $attributes = [])
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
