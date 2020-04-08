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
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Version;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Handles the versions of articles.
 */
class VersionController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var ListRestHelperInterface
     */
    private $restHelper;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        DocumentManagerInterface $documentManager,
        ListRestHelperInterface $restHelper,
        UserRepositoryInterface $userRepository,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->documentManager = $documentManager;
        $this->restHelper = $restHelper;
        $this->userRepository = $userRepository;
    }

    /**
     * Returns the versions for the article with the given UUID.
     *
     * @param string $uuid
     *
     * @return Response
     */
    public function cgetAction(Request $request, $uuid)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);

        $document = $this->documentManager->find($uuid, $request->query->get('locale'));
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

        $limit = $this->restHelper->getLimit();

        $versions = array_slice($versions, $this->restHelper->getOffset(), $limit);

        $userIds = array_unique(
            array_map(
                function($version) {
                    /** @var Version $version */
                    return $version->getAuthor();
                },
                $versions
            )
        );

        $users = $this->userRepository->findUsersById($userIds);
        $fullNamesByIds = [];
        foreach ($users as $user) {
            $fullNamesByIds[$user->getId()] = $user->getContact()->getFullName();
        }

        $versionData = [];
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
            $this->restHelper->getPage(),
            $limit,
            $total
        );

        return $this->handleView($this->view($versionCollection));
    }

    /**
     * @param string $uuid
     * @param int $version
     *
     * @Post("/articles/{uuid}/versions/{version}")
     *
     * @return Response
     *
     * @throws RestException
     */
    public function postTriggerAction(Request $request, $uuid, $version)
    {
        $action = $this->getRequestParameter($request, 'action', true);
        $locale = $this->getLocale($request);

        switch ($action) {
            case 'restore':
                $document = $this->documentManager->find($uuid, $locale);

                $this->documentManager->restore(
                    $document,
                    $locale,
                    str_replace('_', '.', $version)
                );
                $this->documentManager->flush();

                $data = $this->documentManager->find($uuid, $locale);
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

    /**
     * {@inheritdoc}
     */
    public function getSecuredClass()
    {
        return SecurityBehavior::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecuredObjectId(Request $request)
    {
        return $request->get('uuid');
    }
}
