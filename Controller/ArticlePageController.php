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

use FOS\RestBundle\Routing\ClassResourceInterface;
use JMS\Serializer\SerializationContext;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Form\ArticlePageDocumentType;
use Sulu\Bundle\ArticleBundle\Exception\ArticlePageNotFoundException;
use Sulu\Bundle\ArticleBundle\Exception\ParameterNotAllowedException;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides API for article-pages.
 */
class ArticlePageController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    const DOCUMENT_TYPE = 'article_page';

    use RequestParametersTrait;

    /**
     * Returns single article-page.
     *
     * @param string $articleUuid
     * @param string $uuid
     * @param Request $request
     *
     * @return Response
     *
     * @throws ArticlePageNotFoundException
     */
    public function getAction($articleUuid, $uuid, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $document = $this->getDocumentManager()->find(
            $uuid,
            $locale,
            [
                'load_ghost_content' => true,
                'load_shadow_content' => false,
            ]
        );

        if ($articleUuid !== $document->getParent()->getUuid()) {
            // it is required that the parent will be called to resolve the proxy.
            // this wont be done in the serialization process.

            throw new ArticlePageNotFoundException($uuid, $articleUuid);
        }

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()
                    ->setSerializeNull(true)
                    ->setGroups(['defaultPage', 'defaultArticlePage', 'smallArticle'])
            )
        );
    }

    /**
     * Create article-page.
     *
     * @param string $articleUuid
     * @param Request $request
     *
     * @return Response
     */
    public function postAction($articleUuid, Request $request)
    {
        $action = $request->get('action');
        $document = $this->getDocumentManager()->create(self::DOCUMENT_TYPE);
        $locale = $this->getRequestParameter($request, 'locale', true);
        $data = $request->request->all();

        $this->persistDocument($data, $document, $locale, $articleUuid);
        $this->handleActionParameter($action, $document->getParent(), $locale);
        $this->getDocumentManager()->flush();

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()
                    ->setSerializeNull(true)
                    ->setGroups(['defaultPage', 'defaultArticlePage', 'smallArticle'])
            )
        );
    }

    /**
     * Update article-page.
     *
     * @param string $articleUuid
     * @param string $uuid
     * @param Request $request
     *
     * @return Response
     */
    public function putAction($articleUuid, $uuid, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $action = $request->get('action');
        $data = $request->request->all();

        $document = $this->getDocumentManager()->find(
            $uuid,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        $this->get('sulu_hash.request_hash_checker')->checkHash($request, $document, $document->getUuid());

        $this->persistDocument($data, $document, $locale, $articleUuid);
        $this->handleActionParameter($action, $document->getParent(), $locale);
        $this->getDocumentManager()->flush();

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()
                    ->setSerializeNull(true)
                    ->setGroups(['defaultPage', 'defaultArticlePage', 'smallArticle'])
            )
        );
    }

    /**
     * Delete article-page.
     *
     * @param string $articleUuid
     * @param string $uuid
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction($articleUuid, $uuid, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);

        $documentManager = $this->getDocumentManager();
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
            throw new ParameterNotAllowedException('title', ArticlePageDocument::class);
        }

        $article = $this->getDocumentManager()->find($articleUuid, $locale);
        if (!array_key_exists('template', $data)) {
            $data['template'] = $article->getStructureType();
        }

        $form = $this->createForm(
            ArticlePageDocumentType::class,
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

        $this->getDocumentManager()->persist(
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
     * Returns document-manager.
     *
     * @return DocumentManagerInterface
     */
    protected function getDocumentManager()
    {
        return $this->get('sulu_document_manager.document_manager');
    }

    /**
     * @return ContentMapperInterface
     */
    protected function getMapper()
    {
        return $this->get('sulu.content.mapper');
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
                $this->getDocumentManager()->publish($document, $locale);
                break;
        }
    }
}
