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
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Exception\ArticlePageNotFoundException;
use Sulu\Bundle\ArticleBundle\Exception\ParameterNotAllowedException;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
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
     * @throws ArticlePageNotFoundException
     */
    public function getAction(string $articleUuid, string $uuid, Request $request): Response
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $document = $this->getDocumentManager()->find(
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
     */
    public function postAction(string $articleUuid, Request $request): Response
    {
        $action = $request->get('action');
        $document = $this->getDocumentManager()->create(self::DOCUMENT_TYPE);

        $locale = $this->getRequestParameter($request, 'locale', true);
        $data = $request->request->all();

        $this->persistDocument($data, $document, $locale, $articleUuid);
        $this->handleActionParameter($action, $document->getParent(), $locale);
        $this->getDocumentManager()->flush();

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticlePage', 'smallArticle']);

        return $this->handleView(
            $this->view($document)->setContext($context)
        );
    }

    /**
     * Update article-page.
     */
    public function putAction(string $articleUuid, string $uuid, Request $request): Response
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

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticlePage', 'smallArticle']);

        return $this->handleView(
            $this->view($document)->setContext($context)
        );
    }

    /**
     * Delete article-page.
     */
    public function deleteAction(string $articleUuid, string $uuid, Request $request): Response
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
     * @throws InvalidFormException
     * @throws MissingParameterException
     * @throws ParameterNotAllowedException
     */
    private function persistDocument(array $data, object $document, string $locale, string $articleUuid): void
    {
        if (array_key_exists('title', $data)) {
            throw new ParameterNotAllowedException('title', get_class($document));
        }

        $article = $this->getDocumentManager()->find($articleUuid, $locale);

        if (!array_key_exists('template', $data)) {
            $data['template'] = $article->getStructureType();
        }

        $formType = $this->getMetadataFactory()->getMetadataForAlias('article_page')->getFormType();

        $form = $this->createForm(
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
     * @return BaseMetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this->get('sulu_document_manager.metadata_factory.base');
    }

    /**
     * Delegates actions by given actionParameter, which can be retrieved from the request.
     */
    private function handleActionParameter(string $actionParameter, object $document, string $locale): void
    {
        switch ($actionParameter) {
            case 'publish':
                $this->getDocumentManager()->publish($document, $locale);
                break;
        }
    }
}
