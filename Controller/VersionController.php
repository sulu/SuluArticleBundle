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

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Version;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the versions of articles.
 */
class VersionController extends FOSRestController implements
    ClassResourceInterface,
    SecuredControllerInterface
{
    use RequestParametersTrait;

    /**
     * Returns the versions for the article with the given UUID.
     */
    public function cgetAction(Request $request, string $uuid): Response
    {
        $locale = $this->getRequestParameter($request, 'locale', true);

        $document = $this->get('sulu_document_manager.document_manager')->find($uuid, $request->query->get('locale'));
        $versions = array_reverse(
            array_filter(
                $document->getVersions(),
                function($version) use ($locale) {
                    /** @var Version $version */
                    return $version->getLocale() === $locale;
                }
            )
        );
        $total = count($versions);

        $listRestHelper = $this->get('sulu_core.list_rest_helper');
        $limit = $listRestHelper->getLimit();

        $versions = array_slice($versions, $listRestHelper->getOffset(), $limit);

        $userIds = array_unique(
            array_map(
                function($version) {
                    /** @var Version $version */
                    return $version->getAuthor();
                },
                $versions
            )
        );

        $users = $this->get('sulu_security.user_repository')->findUsersById($userIds);
        $fullNamesByIds = [];
        foreach ($users as $user) {
            $fullNamesByIds[$user->getId()] = $user->getFullName();
        }

        $versionData = [];
        /** @var Version $version */
        foreach ($versions as $version) {
            $versionData[] = [
                'id' => str_replace('.', '_', $version->getId()),
                'locale' => $version->getLocale(),
                'author' => array_key_exists($version->getAuthor(), $fullNamesByIds)
                    ? $fullNamesByIds[$version->getAuthor()] : '',
                'authored' => $version->getAuthored(),
            ];
        }

        $versionCollection = new ListRepresentation(
            $versionData,
            'versions',
            $request->attributes->get('_route'),
            [
                'uuid' => $uuid,
                'locale' => $locale,
            ],
            $listRestHelper->getPage(),
            $limit,
            $total
        );

        return $this->handleView($this->view($versionCollection));
    }

    /**
     * @Post("/articles/{uuid}/versions/{version}")
     *
     * @throws RestException
     */
    public function postTriggerAction(Request $request, string $uuid, int $version): Response
    {
        $action = $this->getRequestParameter($request, 'action', true);
        $locale = $this->getLocale($request);

        switch ($action) {
            case 'restore':
                $document = $this->getDocumentManager()->find($uuid, $locale);

                $this->getDocumentManager()->restore(
                    $document,
                    $locale,
                    str_replace('_', '.', $version)
                );
                $this->getDocumentManager()->flush();

                $data = $this->getDocumentManager()->find($uuid, $locale);
                $view = $this->view($data, null !== $data ? Response::HTTP_OK : Response::HTTP_NO_CONTENT);

                $context = new Context();
                $context->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage']);
                $context->setSerializeNull(true);
                $view->setContext($context);

                break;
            default:
                throw new RestException(sprintf('Unrecognized action: "%s"', $action));
        }

        return $this->handleView($view);
    }

    /**
     * @return DocumentManagerInterface
     */
    protected function getDocumentManager()
    {
        return $this->get('sulu_document_manager.document_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return ArticleAdmin::SECURITY_CONTEXT;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(Request $request)
    {
        return $this->getRequestParameter($request, 'locale', true);
    }

    public function getSecuredClass(): string
    {
        return SecurityBehavior::class;
    }

    /**
     * @return mixed
     */
    public function getSecuredObjectId(Request $request)
    {
        return $request->get('uuid');
    }
}
