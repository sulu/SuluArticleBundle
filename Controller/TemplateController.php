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

use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Manages template for articles.
 */
class TemplateController extends Controller
{
    use StructureTagTrait;

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

    /**
     * Renders template for settings tab in edit form.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return Response
     */
    public function settingsAction(Request $request)
    {
        return $this->render(
            'SuluArticleBundle::settings.html.twig',
            [
                'versioning' => $this->getParameter('sulu_document_manager.versioning.enabled'),
            ]
        );
    }
}
