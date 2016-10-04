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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles articles.
 */
class WebsiteArticleController extends Controller
{
    /**
     * @param string $view
     * @param ArticleDocument $object
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction($view, ArticleDocument $object, Request $request)
    {
        if ($object->getAuthored() > new \DateTime()) {
            throw $this->createNotFoundException();
        }

        $categoryManager = $this->get('sulu_category.category_manager');

        $content = $this->resolve($object);
        $categories = $categoryManager->findByIds($content['extension']['excerpt']['categories']);

        return $this->render(
            $view . '.html.twig',
            array_merge(
                [
                    'categories' => $categoryManager->getApiObjects($categories, $request->getLocale())
                ],
                $content
            )
        );
    }

    /**
     * @param ArticleDocument $article
     *
     * @return array
     */
    protected function resolve(ArticleDocument $article)
    {
        return $this->get('jms_serializer')->serialize(
            $article,
            'array',
            SerializationContext::create()
                ->setSerializeNull(true)
                ->setGroups(['website', 'content'])
                ->setAttribute('website', true)
        );
    }
}
