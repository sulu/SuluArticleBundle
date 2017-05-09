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
use Sulu\Component\HttpCache\HttpCache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    public function indexAction(Request $request, ArticleInterface $object, $view, $pageNumber = 1)
    {
        return $this->renderArticle($request, $object, $view, $pageNumber);
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
    protected function renderArticle(Request $request, ArticleInterface $object, $view, $pageNumber, $attributes = [])
    {
        $object = $this->normalizeArticle($object);

        $requestFormat = $request->getRequestFormat();
        $viewTemplate = $view . '.' . $requestFormat . '.twig';

        $content = $this->serializeArticle($object, $pageNumber);

        return $this->render(
            $viewTemplate,
            $this->get('sulu_website.resolver.template_attribute')->resolve(array_merge($content, $attributes)),
            $this->createResponse($request)
        );
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
    protected function serializeArticle(ArticleInterface $object, $pageNumber)
    {
        return $this->get('jms_serializer')->serialize(
            $object,
            'array',
            SerializationContext::create()
                ->setSerializeNull(true)
                ->setGroups(['website', 'content'])
                ->setAttribute('website', true)
                ->setAttribute('pageNumber', $pageNumber)
        );
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
                HttpCache::HEADER_REVERSE_PROXY_TTL,
                $cacheLifetime
            );
        }

        return $response;
    }
}
