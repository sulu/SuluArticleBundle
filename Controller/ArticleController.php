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

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use JMS\Serializer\SerializationContext;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\IdsQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\MultiMatchQuery;
use ONGR\ElasticsearchDSL\Query\TermQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use Sulu\Bundle\ArticleBundle\Document\Form\ArticleDocumentType;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
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
    use ArticleViewDocumentIdTrait;

    /**
     * Create field-descriptor array.
     *
     * @return FieldDescriptor[]
     */
    private function getFieldDescriptors()
    {
        return [
            'uuid' => new FieldDescriptor('uuid', 'public.id', true),
            'typeTranslation' => new FieldDescriptor(
                'typeTranslation',
                'sulu_article.list.type',
                !$this->getParameter('sulu_article.display_tab_all'),
                false
            ),
            'title' => new FieldDescriptor('title', 'public.title', false, true),
            'creatorFullName' => new FieldDescriptor('creatorFullName', 'sulu_article.list.creator', true, false),
            'changerFullName' => new FieldDescriptor('changerFullName', 'sulu_article.list.changer', false, false),
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
        $locale = $this->getRequestParameter($request, 'locale', true);

        $restHelper = $this->get('sulu_core.list_rest_helper');

        /** @var Manager $manager */
        $manager = $this->get('es.manager.default');
        $repository = $manager->getRepository($this->get('sulu_article.view_document.factory')->getClass('article'));
        $search = $repository->createSearch();

        $limit = (int) $restHelper->getLimit();
        $page = (int) $restHelper->getPage();

        if (null !== $locale) {
            $search->addQuery(new TermQuery('locale', $locale));
        }

        if (count($ids = array_filter(explode(',', $request->get('ids', ''))))) {
            $search->addQuery(new IdsQuery($this->getViewDocumentIds($ids, $locale)));
            $limit = count($ids);
        }

        if (!empty($searchPattern = $restHelper->getSearchPattern())
            && 0 < count($searchFields = $restHelper->getSearchFields())
        ) {
            $search->addQuery(new MultiMatchQuery($searchFields, $searchPattern));
        }

        if (null !== ($type = $request->get('type'))) {
            $search->addQuery(new TermQuery('type', $type));
        }

        if (null === $search->getQueries()) {
            $search->addQuery(new MatchAllQuery());
        }

        $count = $repository->count($search);

        if (null !== $restHelper->getSortColumn()) {
            $search->addSort(
                new FieldSort($this->uncamelize($restHelper->getSortColumn()), $restHelper->getSortOrder())
            );
        }

        $search->setSize($limit);
        $search->setFrom(($page - 1) * $limit);

        $result = [];
        foreach ($repository->execute($search) as $document) {
            if (false !== ($index = array_search($document->getUuid(), $ids))) {
                $result[$index] = $document;
            } else {
                $result[] = $document;
            }
        }

        if (count($ids)) {
            ksort($result);
            $result = array_values($result);
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
                'load_ghost_content' => true,
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
                'load_ghost_content' => true,
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
     * Trigger a action for given article specified over get-action parameter.
     *
     * @Post("/articles/{uuid}")
     *
     * @param string $uuid
     * @param Request $request
     *
     * @return Response
     */
    public function postTriggerAction($uuid, Request $request)
    {
        // extract parameter
        $action = $this->getRequestParameter($request, 'action', true);
        $locale = $this->getRequestParameter($request, 'locale', true);

        // prepare vars
        $view = null;
        $data = null;

        try {
            switch ($action) {
                case 'unpublish':
                    $document = $this->getDocumentManager()->find($uuid, $locale);
                    $this->getDocumentManager()->unpublish($document, $locale);
                    $this->getDocumentManager()->flush();

                    $data = $this->getDocumentManager()->find($uuid, $locale);
                    break;
                case 'remove-draft':
                    $data = $this->getDocumentManager()->find($uuid, $locale);
                    $this->getDocumentManager()->removeDraft($data, $locale);
                    $this->getDocumentManager()->flush();
                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // prepare view
            $view = $this->view($data, $data !== null ? 200 : 204);
            $view->setSerializationContext(SerializationContext::create()->setGroups(['defaultPage']));
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
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
                'route_path' => array_key_exists('routePath', $data) ? $data['routePath'] : null,
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
            return [$this->getUser()->getContact()->getId()];
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

    /**
     * Converts camel case string into normalized string with underscore.
     *
     * @param string $camel
     *
     * @return string
     */
    private function uncamelize($camel)
    {
        $camel = preg_replace(
            '/(?!^)[[:upper:]][[:lower:]]/',
            '$0',
            preg_replace('/(?!^)[[:upper:]]+/', '_$0', $camel)
        );

        return strtolower($camel);
    }
}
