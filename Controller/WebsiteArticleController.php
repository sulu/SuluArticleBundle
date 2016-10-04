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
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles articles.
 */
class WebsiteArticleController extends Controller
{
    /**
     * @param string $view
     * @param ArticleDocument $object
     *
     * @return Response
     */
    public function indexAction($view, ArticleDocument $object)
    {
        $content = $this->get('jms_serializer')->serialize(
            $object,
            'array',
            SerializationContext::create()
                ->setSerializeNull(true)
                ->setGroups(['website', 'content'])
                ->setAttribute('website', true)
        );

        return $this->render(
            $view . '.html.twig',
            $content
        );
    }
}
