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

use Sulu\Bundle\ArticleBundle\Util\TypeTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Manages template for articles.
 */
class TemplateController extends Controller
{
    use TypeTrait;

    /**
     * Returns template for given article type.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $structureProvider = $this->get('sulu.content.structure_manager');
        $type = $request->get('type', 'default');

        $templates = [];
        foreach ($structureProvider->getStructures('article') as $structure) {
            if ($this->getType($structure->getStructure()) !== $type) {
                continue;
            }

            $templates[] = [
                'internal' => $structure->getInternal(),
                'template' => $structure->getKey(),
                'title' => $structure->getLocalizedTitle($this->getUser()->getLocale()),
            ];
        }

        return new JsonResponse(
            [
                '_embedded' => $templates,
                'total' => count($templates),
            ]
        );
    }
}
