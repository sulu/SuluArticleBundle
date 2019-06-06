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

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use ONGR\ElasticsearchBundle\Mapping\Caser;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhrasePrefixQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\ListBuilder\ElasticSearchFieldDescriptor;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
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
     * @return ElasticSearchFieldDescriptor[]
     */
    protected function getFieldDescriptors()
    {
        return [
            'uuid' => ElasticSearchFieldDescriptor::create('id', 'public.id')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'typeTranslation' => ElasticSearchFieldDescriptor::create('typeTranslation', 'sulu_article.list.type')
                ->setSortField('typeTranslation.raw')
                ->setVisibility(
                    $this->getParameter('sulu_article.display_tab_all') ?
                        FieldDescriptorInterface::VISIBILITY_YES :
                        FieldDescriptorInterface::VISIBILITY_NEVER
                )
                ->build(),
            'title' => ElasticSearchFieldDescriptor::create('title', 'public.title')
                ->setSortField('title.raw')
                ->build(),
            'creatorFullName' => ElasticSearchFieldDescriptor::create('creatorFullName', 'sulu_article.list.creator')
                ->setSortField('creatorFullName.raw')
                ->build(),
            'changerFullName' => ElasticSearchFieldDescriptor::create('changerFullName', 'sulu_article.list.changer')
                ->setSortField('changerFullName.raw')
                ->build(),
            'authorFullName' => ElasticSearchFieldDescriptor::create('authorFullName', 'sulu_article.author')
                ->setSortField('authorFullName.raw')
                ->build(),
            'created' => ElasticSearchFieldDescriptor::create('created', 'public.created')
                ->setSortField('authored')
                ->setType('datetime')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'changed' => ElasticSearchFieldDescriptor::create('changed', 'public.changed')
                ->setSortField('authored')
                ->setType('datetime')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'authored' => ElasticSearchFieldDescriptor::create('authored', 'sulu_article.authored')
                ->setSortField('authored')
                ->setType('datetime')
                ->build(),
            'localizationState' => ElasticSearchFieldDescriptor::create('localizationState')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'published' => ElasticSearchFieldDescriptor::create('published')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'publishedState' => ElasticSearchFieldDescriptor::create('publishedState')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
            'routePath' => ElasticSearchFieldDescriptor::create('routePath')
                ->setVisibility(FieldDescriptorInterface::VISIBILITY_NO)
                ->build(),
        ];
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
            $boolQuery = new BoolQuery();
            foreach ($searchFields as $searchField) {
                $boolQuery->add(new MatchPhrasePrefixQuery($searchField, $searchPattern), BoolQuery::SHOULD);
            }
            $search->addQuery($boolQuery);
        }

        if (null !== ($type = $request->get('type'))) {
            $search->addQuery(new TermQuery('type', $type));
        }

        if ($contactId = $request->get('contactId')) {
            $boolQuery = new BoolQuery();
            $boolQuery->add(new MatchQuery('changer_contact_id', $contactId), BoolQuery::SHOULD);
            $boolQuery->add(new MatchQuery('creator_contact_id', $contactId), BoolQuery::SHOULD);
            $boolQuery->add(new MatchQuery('author_id', $contactId), BoolQuery::SHOULD);
            $search->addQuery($boolQuery);
        }

        if ($categoryId = $request->get('categoryId')) {
            $search->addQuery(new TermQuery('excerpt.categories.id', $categoryId), BoolQuery::MUST);
        }

        if ($tagId = $request->get('tagId')) {
            $search->addQuery(new TermQuery('excerpt.tags.id', $tagId), BoolQuery::MUST);
        }

        if ($pageId = $request->get('pageId')) {
            $search->addQuery(new TermQuery('parent_page_uuid', $pageId), BoolQuery::MUST);
        }

        if ($workflowStage = $request->get('workflowStage')) {
            $search->addQuery(new TermQuery('published_state', 'published' === $workflowStage), BoolQuery::MUST);
        }

        if ($this->getBooleanRequestParameter($request, 'exclude-shadows', false, false)) {
            $search->addQuery(new TermQuery('localization_state.state', 'shadow'), BoolQuery::MUST_NOT);
        }

        if ($this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false)) {
            $search->addQuery(new TermQuery('localization_state.state', 'ghost'), BoolQuery::MUST_NOT);
        }

        $authoredFrom = $request->get('authoredFrom');
        $authoredTo = $request->get('authoredTo');
        if ($authoredFrom || $authoredTo) {
            $search->addQuery($this->getRangeQuery('authored', $authoredFrom, $authoredTo), BoolQuery::MUST);
        }

        if (null === $search->getQueries()) {
            $search->addQuery(new MatchAllQuery());
        }

        if (null !== $restHelper->getSortColumn() &&
            $sortField = $this->getSortFieldName($restHelper->getSortColumn())
        ) {
            $search->addSort(
                new FieldSort($sortField, $restHelper->getSortOrder())
            );
        }

        $fieldDescriptors = $this->getFieldDescriptors();

        if ($limit) {
            $search->setSize($limit);
            $search->setFrom(($page - 1) * $limit);

            $fields = array_merge(
                $restHelper->getFields() ?: [],
                ['id', 'localizationState', 'publishedState', 'published', 'title', 'routePath']
            );
            $fieldDescriptors = array_filter(
                $fieldDescriptors,
                function (FieldDescriptorInterface $fieldDescriptor) use ($fields) {
                    return in_array($fieldDescriptor->getName(), $fields);
                }
            );
        } else {
            $search->setSize(1000);
            $search->setScroll('1m');
        }

        $searchResult = $repository->findRaw($search);
        $result = [];
        foreach ($searchResult as $document) {
            $documentData = $this->normalize($document['_source'], $fieldDescriptors);
            if (false !== ($index = array_search($documentData['id'], $ids))) {
                $result[$index] = $documentData;
            } else {
                $result[] = $documentData;
            }
        }

        if (count($ids)) {
            ksort($result);
            $result = array_values($result);
        }

        $count = $searchResult->count();

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
     * @param FieldDescriptorInterface[] $fieldDescriptors
     */
    private function normalize(array $document, array $fieldDescriptors)
    {
        $result = [];
        foreach ($fieldDescriptors as $fieldDescriptor) {
            $property = Caser::snake($fieldDescriptor->getName());
            if ('id' === $property) {
                $property = 'uuid';
            }

            $result[$fieldDescriptor->getName()] = array_key_exists($property, $document) ? $document[$property] : null;
        }

        return $result;
    }

    /**
     * Returns query to filter by given range.
     *
     * @param string $field
     * @param string $from
     * @param string $to
     *
     * @return RangeQuery
     */
    private function getRangeQuery($field, $from, $to)
    {
        return new RangeQuery($field, array_filter(['gte' => $from, 'lte' => $to]));
    }

    /**
     * Returns single article.
     *
     * @param Request $request
     * @param string $id
     *
     * @return Response
     *
     * @Get(defaults={"id" = ""})
     */
    public function getAction(Request $request, $id)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $document = $this->getDocumentManager()->find(
            $id,
            $locale
        );

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage']);

        return $this->handleView(
            $this->view($document)->setContext($context)
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

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage']);

        return $this->handleView(
            $this->view($document)->setContext($context)
        );
    }

    /**
     * Update articles.
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $action = $request->get('action');
        $data = $request->request->all();

        $document = $this->getDocumentManager()->find(
            $id,
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

        $context = new Context();
        $context->setSerializeNull(true);
        $context->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage']);

        return $this->handleView(
            $this->view($document)->setContext($context)
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
     * @Post("/articles/{id}")
     *
     * @param string  $uuid
     * @param Request $request
     *
     * @return Response
     */
    public function postTriggerAction($id, Request $request)
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
                    $document = $this->getDocumentManager()->find($id, $locale);
                    $this->getDocumentManager()->unpublish($document, $locale);
                    $this->getDocumentManager()->flush();

                    $data = $this->getDocumentManager()->find($id, $locale);

                    break;
                case 'remove-draft':
                    $data = $this->getDocumentManager()->find($id, $locale);
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

                    $this->getMapper()->copyLanguage($id, $userId, null, $locale, $destLocales);

                    $data = $this->getDocumentManager()->find($id, $locale);

                    break;
                case 'copy':
                    /** @var ArticleDocument $document */
                    $document = $this->getDocumentManager()->find($id, $locale);
                    $copiedPath = $this->getDocumentManager()->copy($document, dirname($document->getPath()));
                    $this->getDocumentManager()->flush();

                    $data = $this->getDocumentManager()->find($copiedPath, $locale);

                    break;
                case 'order':
                    $this->orderPages($this->getRequestParameter($request, 'pages', true), $locale);
                    $this->getDocumentManager()->flush();
                    $this->getDocumentManager()->clear();

                    $data = $this->getDocumentManager()->find($id, $locale);

                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // create context
            $context = new Context();
            $context->setSerializeNull(true);
            $context->setGroups(['defaultPage', 'defaultArticle', 'smallArticlePage']);

            // prepare view
            $view = $this->view($data);
            $view->setContext($context);
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
     * Persists the document using the given Formation.
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
        $formType = $this->getMetadataFactory()->getMetadataForAlias('article')->getFormType();
        $form = $this->createForm(
            $formType,
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

        if (array_key_exists('additionalWebspaces', $data) && null === $data['additionalWebspaces']) {
            $document->setAdditionalWebspaces(null);
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
        $sortBy = Caser::snake($sortBy);
        $fieldDescriptors = $this->getFieldDescriptors();

        if (array_key_exists($sortBy, $fieldDescriptors)) {
            return $fieldDescriptors[$sortBy]->getSortField();
        }

        return null;
    }

    /**
     * @return BaseMetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this->get('sulu_document_manager.metadata_factory.base');
    }
}
