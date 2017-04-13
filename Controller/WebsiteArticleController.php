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
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
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
     * @param int $pageNumber
     * @param string $view
     *
     * @return Response
     */
    public function indexAction(Request $request, ArticleInterface $object, $pageNumber, $view)
    {
        $content = $this->get('jms_serializer')->serialize(
            $object,
            'array',
            SerializationContext::create()
                ->enableMaxDepthChecks()
                ->setSerializeNull(true)
                ->setGroups(['website', 'content'])
                ->setAttribute('website', true)
                ->setAttribute('pageNumber', $pageNumber)
        );

        return $this->render(
            $view . '.html.twig',
            $this->get('sulu_website.resolver.template_attribute')->resolve($content),
            $this->createResponse($request)
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
