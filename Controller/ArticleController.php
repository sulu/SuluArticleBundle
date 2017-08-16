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
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MultiMatchQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Form\ArticleDocumentType;
use Sulu\Bundle\ArticleBundle\FieldDescriptor\ESFieldDescriptor;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides API for articles.
 */
class ArticleController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    const DOCUMENT_TYPE = 'article';

    use RequestParametersTrait;
    use ArticleViewDocumentIdTrait;

    /**
     * Create field-descriptor array.
     *
     * @return ESFieldDescriptor[]
     */
    private function getFieldDescriptors()
    {
        return [
            'uuid' => new ESFieldDescriptor('id', '', 'public.id', false, false, 'string', '', '', false),
            'typeTranslation' => new ESFieldDescriptor(
                'typeTranslation',
                'typeTranslation.raw',
                'sulu_article.list.type',
                !$this->getParameter('sulu_article.display_tab_all'),
                false
            ),
            'title' => new ESFieldDescriptor('title', 'title.raw', 'public.title', false, true),
            'creatorFullName' => new ESFieldDescriptor(
                'creatorFullName',
                'creatorFullName.raw',
                'sulu_article.list.creator',
                true,
                false
            ),
            'changerFullName' => new ESFieldDescriptor(
                'changerFullName',
                'changerFullName.raw',
                'sulu_article.list.changer',
                false,
                false
            ),
            'authorFullName' => new ESFieldDescriptor(
                'authorFullName',
                'authorFullName.raw',
                'sulu_article.author',
                false,
                false
            ),
            'created' => new ESFieldDescriptor('created', '', 'public.created', true, false, 'datetime'),
            'changed' => new ESFieldDescriptor('changed', '', 'public.changed', false, false, 'datetime'),
            'authored' => new ESFieldDescriptor('authored', '', 'sulu_article.authored', false, false, 'date'),
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

        if (null !== ($contactId = $request->get('contactId'))) {
            $boolQuery = new BoolQuery();
            $boolQuery->add(new MatchQuery('changer_contact_id', $contactId), BoolQuery::SHOULD);
            $boolQuery->add(new MatchQuery('creator_contact_id', $contactId), BoolQuery::SHOULD);
            $boolQuery->add(new MatchQuery('author_id', $contactId), BoolQuery::SHOULD);
            $search->addQuery($boolQuery);
        }

        if (null !== ($categoryId = $request->get('categoryId'))) {
            $search->addQuery(new TermQuery('excerpt.categories.id', $categoryId), BoolQuery::MUST);
        }

        if (null === $search->getQueries()) {
            $search->addQuery(new MatchAllQuery());
        }

        $count = $repository->count($search);

        if (null !== $restHelper->getSortColumn() &&
            $sortField = $this->getSortFieldName($restHelper->getSortColumn())
        ) {
            $search->addSort(
                new FieldSort($sortField, $restHelper->getSortOrder())
            );
        }

        $search->setSize($limit);
        $search->setFrom(($page - 1) * $limit);

        $result = [];
        foreach ($repository->findDocuments($search) as $document) {
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
     * @param string  $uuid
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
                SerializationContext::create()
                    ->setSerializeNull(true)
                    ->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage'])
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

        $this->persistDocument($data, $document, $locale);
        $this->handleActionParameter($action, $document, $locale);
        $this->getDocumentManager()->flush();

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()
                    ->setSerializeNull(true)
                    ->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage'])
            )
        );
    }

    /**
     * Update articles.
     *
     * @param Request $request
     * @param string  $uuid
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

        $this->persistDocument($data, $document, $locale);
        $this->handleActionParameter($action, $document, $locale);
        $this->getDocumentManager()->flush();

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()
                    ->setSerializeNull(true)
                    ->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage'])
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
     * @param string  $uuid
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
        $userId = $this->getUser()->getId();

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
                case 'copy-locale':
                    $destLocales = $this->getRequestParameter($request, 'dest', true);
                    $destLocales = explode(',', $destLocales);

                    $securityChecker = $this->get('sulu_security.security_checker');
                    foreach ($destLocales as $destLocale) {
                        $securityChecker->checkPermission(
                            new SecurityCondition($this->getSecurityContext(), $destLocale),
                            PermissionTypes::EDIT
                        );
                    }

                    $this->getMapper()->copyLanguage($uuid, $userId, null, $locale, $destLocales);

                    $data = $this->getDocumentManager()->find($uuid, $locale);

                    break;
                case 'copy':
                    /** @var ArticleDocument $document */
                    $document = $this->getDocumentManager()->find($uuid, $locale);
                    $copiedPath = $this->getDocumentManager()->copy($document, dirname($document->getPath()));
                    $this->getDocumentManager()->flush();

                    $data = $this->getDocumentManager()->find($copiedPath, $locale);

                    break;
                case 'order':
                    $this->orderPages($this->getRequestParameter($request, 'pages', true), $locale);
                    $this->getDocumentManager()->flush();
                    $this->getDocumentManager()->clear();

                    $data = $this->getDocumentManager()->find($uuid, $locale);

                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // prepare view
            $view = $this->view($data);
            $view->setSerializationContext(
                SerializationContext::create()
                    ->setSerializeNull(true)
                    ->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage'])
            );
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Ordering given pages.
     *
     * @param array $pages
     * @param string $locale
     */
    private function orderPages(array $pages, $locale)
    {
        $documentManager = $this->getDocumentManager();

        for ($i = 0; $i < count($pages); ++$i) {
            $document = $documentManager->find($pages[$i], $locale);
            $documentManager->reorder($document, null);
        }
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
     * @param array  $data
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

        if (array_key_exists('author', $data) && null === $data['author']) {
            $document->setAuthor(null);
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

    /**
     * @param string $sortBy
     *
     * @return null|string
     */
    private function getSortFieldName($sortBy)
    {
        $sortBy = $this->uncamelize($sortBy);
        $fieldDescriptors = $this->getFieldDescriptors();

        if (array_key_exists($sortBy, $fieldDescriptors)) {
            return $fieldDescriptors[$sortBy]->getSortField();
        }

        return null;
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
