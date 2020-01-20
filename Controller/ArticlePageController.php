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
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Exception\ArticlePageNotFoundException;
use Sulu\Bundle\ArticleBundle\Exception\ParameterNotAllowedException;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\Hash\RequestHashCheckerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides API for article-pages.
 */
class ArticlePageController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    const DOCUMENT_TYPE = 'article_page';

    use RequestParametersTrait;

    /** @var DocumentManagerInterface */
    private $documentManager;

    /** @var MetadataFactoryInterface */
    private $metadataFactory;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var RequestHashCheckerInterface */
    private $requestHashChecker;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        DocumentManagerInterface $documentManager,
        MetadataFactoryInterface $metadataFactory,
        FormFactoryInterface $formFactory,
        RequestHashCheckerInterface $requestHashChecker,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->documentManager = $documentManager;
        $this->metadataFactory = $metadataFactory;
        $this->formFactory = $formFactory;
        $this->requestHashChecker = $requestHashChecker;
    }

    /**
     * Returns single article-page.
     *
     * @param string $articleUuid
     * @param string $uuid
     *
     * @return Response
     *
     * @throws ArticlePageNotFoundException
     */
    public function getAction($articleUuid, $uuid, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $document = $this->documentManager->find(
            $uuid,
            $locale,
            [
                'load_ghost_content' => true,
                'load_shadow_content' => true,
            ]
        );

        if ($articleUuid !== $document->getParent()->getUuid()) {
            // it is required that the parent will be called to resolve the proxy.
            // this wont be done in the serialization process.

            throw new ArticlePageNotFoundException($uuid, $articleUuid);
        }

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticlePage', 'smallArticle']);

        return $this->handleView(
            $this->view($document)->setContext($context)
        );
    }

    /**
     * Create article-page.
     *
     * @param string $articleUuid
     *
     * @return Response
     */
    public function postAction($articleUuid, Request $request)
    {
        $action = $request->get('action');
        $document = $this->documentManager->create(self::DOCUMENT_TYPE);

        $locale = $this->getRequestParameter($request, 'locale', true);
        $data = $request->request->all();

        $this->persistDocument($data, $document, $locale, $articleUuid);
        $this->handleActionParameter($action, $document->getParent(), $locale);
        $this->documentManager->flush();

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticlePage', 'smallArticle']);

        return $this->handleView(
            $this->view($document)->setContext($context)
        );
    }

    /**
     * Update article-page.
     *
     * @param string $articleUuid
     * @param string $uuid
     *
     * @return Response
     */
    public function putAction($articleUuid, $uuid, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $action = $request->get('action');
        $data = $request->request->all();

        $document = $this->documentManager->find(
            $uuid,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        $this->requestHashChecker->checkHash($request, $document, $document->getUuid());

        $this->persistDocument($data, $document, $locale, $articleUuid);
        $this->handleActionParameter($action, $document->getParent(), $locale);
        $this->documentManager->flush();

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticlePage', 'smallArticle']);

        return $this->handleView(
            $this->view($document)->setContext($context)
        );
    }

    /**
     * Delete article-page.
     *
     * @param string $articleUuid
     * @param string $uuid
     *
     * @return Response
     */
    public function deleteAction($articleUuid, $uuid, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);

        $documentManager = $this->documentManager;
        $document = $documentManager->find($uuid, $locale);
        $documentManager->remove($document);
        $documentManager->flush();

        return $this->handleView($this->view(null));
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return ArticleAdmin::SECURITY_CONTEXT;
    }

    /**
     * Persists the document using the given information.
     *
     * @param array $data
     * @param object $document
     * @param string $locale
     * @param string $articleUuid
     *
     * @throws InvalidFormException
     * @throws MissingParameterException
     * @throws ParameterNotAllowedException
     */
    private function persistDocument($data, $document, $locale, $articleUuid)
    {
        if (array_key_exists('title', $data)) {
            throw new ParameterNotAllowedException('title', get_class($document));
        }

        $article = $this->documentManager->find($articleUuid, $locale);

        if (!array_key_exists('template', $data)) {
            $data['template'] = $article->getStructureType();
        }

        $formType = $this->metadataFactory->getMetadataForAlias('article_page')->getFormType();

        $form = $this->formFactory->create(
            $formType,
            $document,
            [
                // disable csrf protection, since we can't produce a token, because the form is cached on the client
                'csrf_protection' => false,
            ]
        );
        $form->submit($data, false);

        $document->setParent($article);

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $this->documentManager->persist(
            $document,
            $locale,
            [
                'user' => $this->getUser()->getId(),
                'clear_missing_content' => false,
                'auto_name' => false,
                'auto_rename' => false,
            ]
        );
    }

    /**
     * Delegates actions by given actionParameter, which can be retrieved from the request.
     *
     * @param string $actionParameter
     * @param object $document
     * @param string $locale
     */
    private function handleActionParameter($actionParameter, $document, $locale)
    {
        switch ($actionParameter) {
            case 'publish':
                $this->documentManager->publish($document, $locale);
                break;
        }
    }
}
