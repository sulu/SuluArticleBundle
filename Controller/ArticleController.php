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
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\TermQuery;
use ONGR\ElasticsearchDSL\Query\WildcardQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use Sulu\Bundle\ArticleBundle\Document\ArticleOngrDocument;
use Sulu\Bundle\ArticleBundle\Document\Form\ArticleDocumentType;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides API for articles.
 */
class ArticleController extends RestController implements ClassResourceInterface
{
    const DOCUMENT_TYPE = 'article';

    use RequestParametersTrait;

    /**
     * Create field-descriptor array.
     *
     * @return FieldDescriptor[]
     */
    private function getFieldDescriptors()
    {
        return [
            'id' => new FieldDescriptor('id', 'public.id', true),
            'title' => new FieldDescriptor('title', 'public.title', false, true),
            'creator' => new FieldDescriptor('creator', 'sulu_article.list.creator', true, false),
            'changer' => new FieldDescriptor('changer', 'sulu_article.list.changer', false, false),
            'created' => new FieldDescriptor('created', 'public.created', true, false, 'datetime'),
            'changed' => new FieldDescriptor('changed', 'public.changed', false, false, 'datetime'),
            'authored' => new FieldDescriptor('authored', 'sulu_article.authored', false, false, 'date'),
        ];
    }

    /**
     * Returns fields.
     *
     * @return Response
     */
    public function cgetFieldsAction()
    {
        $fieldDescriptors = $this->getFieldDescriptors();

        return $this->handleView($this->view(array_values($fieldDescriptors)));
    }

    /**
     * Returns list of articles.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $restHelper = $this->get('sulu_core.list_rest_helper');

        /** @var Manager $manager */
        $manager = $this->get('es.manager.default');
        $repository = $manager->getRepository(ArticleOngrDocument::class);
        $search = $repository->createSearch();

        if (!empty($searchPattern = $restHelper->getSearchPattern())) {
            foreach ($restHelper->getSearchFields() as $searchField) {
                $search->addQuery(new WildcardQuery($searchField, '*' . $searchPattern . '*'));
            }
        }

        if (null !== ($type = $request->get('type'))) {
            $search->addQuery(new TermQuery('type', $type));
        }

        $count = $repository->count($search);

        if (null !== $restHelper->getSortColumn()) {
            $search->addSort(new FieldSort($restHelper->getSortColumn(), $restHelper->getSortOrder()));
        }

        $limit = (int) $restHelper->getLimit();
        $page = (int) $restHelper->getPage();
        $search->setSize($limit);
        $search->setFrom(($page - 1) * $limit);

        $result = [];
        foreach ($repository->execute($search) as $document) {
            $result[] = $document;
        }

        return $this->handleView(
            $this->view(
                new ListRepresentation(
                    $result,
                    'articles',
                    'get_articles',
                    $request->query->all(),
                    $page,
                    $limit,
                    $count
                )
            )
        );
    }

    /**
     * Returns single article.
     *
     * @param string $uuid
     * @param Request $request
     *
     * @return Response
     */
    public function getAction($uuid, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $document = $this->getDocumentManager()->find(
            $uuid,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()->setSerializeNull(true)->setGroups(['defaultPage'])
            )
        );
    }

    /**
     * Create article.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $action = $request->get('action');
        $document = $this->getDocumentManager()->create(self::DOCUMENT_TYPE);
        $locale = $this->getRequestParameter($request, 'locale', true);
        $data = $request->request->all();

        $document->setAuthored(new \DateTime());
        if (array_key_exists('authored', $data)) {
            $document->setAuthored(new \DateTime($data['authored']));
        }
        $document->setAuthors($this->getAuthors($data));

        $this->persistDocument($data, $document, $locale);
        $this->handleActionParameter($action, $document, $locale);
        $this->getDocumentManager()->flush();

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()->setSerializeNull(true)->setGroups(['defaultPage'])
            )
        );
    }

    /**
     * Update articles.
     *
     * @param Request $request
     * @param string $uuid
     *
     * @return Response
     */
    public function putAction(Request $request, $uuid)
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

        if (array_key_exists('authored', $data)) {
            $document->setAuthored(new \DateTime($data['authored']));
        }
        $document->setAuthors($this->getAuthors($data));

        $this->persistDocument($data, $document, $locale);
        $this->handleActionParameter($action, $document, $locale);
        $this->getDocumentManager()->flush();

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()->setSerializeNull(true)->setGroups(['defaultPage'])
            )
        );
    }

    /**
     * Deletes multiple documents.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cdeleteAction(Request $request)
    {
        $ids = array_filter(explode(',', $request->get('ids', '')));

        $documentManager = $this->getDocumentManager();
        foreach ($ids as $id) {
            $document = $documentManager->find($id);
            $documentManager->remove($document);
            $documentManager->flush();
        }

        return $this->handleView($this->view(null));
    }

    /**
     * Deletes multiple documents.
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $documentManager = $this->getDocumentManager();
        $document = $documentManager->find($id);
        $documentManager->remove($document);
        $documentManager->flush();

        return $this->handleView($this->view(null));
    }

    /**
     * Persists the document using the given information.
     *
     * @param array $data
     * @param object $document
     * @param string $locale
     *
     * @throws InvalidFormException
     * @throws MissingParameterException
     */
    private function persistDocument($data, $document, $locale)
    {
        $form = $this->createForm(
            ArticleDocumentType::class,
            $document,
            [
                // disable csrf protection, since we can't produce a token, because the form is cached on the client
                'csrf_protection' => false,
            ]
        );
        $form->submit($data, false);

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $this->getDocumentManager()->persist(
            $document,
            $locale,
            [
                'user' => $this->getUser()->getId(),
                'clear_missing_content' => false,
            ]
        );
    }

    /**
     * Returns authors or current user.
     *
     * @param array $data
     *
     * @return int[]
     */
    private function getAuthors(array $data)
    {
        if (!array_key_exists('authors', $data)) {
            return [$this->getUser()->getId()];
        }

        return $data['authors'];
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
