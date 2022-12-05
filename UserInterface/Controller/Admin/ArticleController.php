<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\UserInterface\Controller\Admin;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\ControllerTrait;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ArticleBundle\Application\Message\ApplyWorkflowTransitionArticleMessage;
use Sulu\Bundle\ArticleBundle\Application\Message\CopyLocaleArticleMessage;
use Sulu\Bundle\ArticleBundle\Application\Message\CreateArticleMessage;
use Sulu\Bundle\ArticleBundle\Application\Message\ModifyArticleMessage;
use Sulu\Bundle\ArticleBundle\Application\Message\RemoveArticleMessage;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class should not be instated by a project
 *           Use instead a request or response listener to
 *           extend the endpoints behaviours
 */
final class ArticleController implements ClassResourceInterface
{
    use ControllerTrait;
    use HandleTrait;

    /**
     * @var ArticleRepositoryInterface
     */
    private $articleRepository;

    /**
     * @var ContentManagerInterface
     */
    private $contentManager;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $listBuilderFactory;

    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        MessageBusInterface $messageBus,
        ViewHandlerInterface $viewhandler,
        ContentManagerInterface $contentManager,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        RestHelperInterface $restHelper
    ) {
        $this->articleRepository = $articleRepository;
        $this->messageBus = $messageBus;
        $this->setViewHandler($viewhandler);

        // TODO controller should not need more then Repository, MessageBus, Serializer
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->listBuilderFactory = $listBuilderFactory;
        $this->restHelper = $restHelper;
        $this->contentManager = $contentManager;
    }

    public function cgetAction(Request $request): Response
    {
        // TODO this should be ArticleRepository::findFlatBy / ::countFlatBy methods
        //      but first we would need to avoid that the restHelper requires the request.
        //
        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(ArticleInterface::RESOURCE_KEY);
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(ArticleInterface::class);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['ghostLocale']);
        $listBuilder->setParameter('locale', $request->query->get('locale'));
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            ArticleInterface::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count()
        );

        return $this->handleView($this->view($listRepresentation));
    }

    public function getAction(Request $request, string $id): Response // TODO route should be a uuid
    {
        $dimensionAttributes = [
            'locale' => (string) $request->query->get('locale', $request->getLocale()),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        $article = $this->articleRepository->getOneBy(
            \array_merge(
                [
                    'uuid' => $id,
                    'load_ghost_content' => true,
                ],
                $dimensionAttributes,
            ),
            [
                ArticleRepositoryInterface::GROUP_SELECT_ARTICLE_ADMIN => true,
            ]
        );

        // TODO the `$article` should just be serialized here with `['article_admin', 'content_admin']`
        //      Instead of calling the content resolver service which triggers an additional query.
        $dimensionContent = $this->contentManager->resolve($article, $dimensionAttributes);
        $normalizedContent = $this->contentManager->normalize($dimensionContent);

        return $this->handleView(
            $this->view($normalizedContent, 200)
                ->setContext((new Context())->setSerializeNull(null)->setGroups(['article_admin', 'content_admin']))
        );
    }

    public function postAction(Request $request): Response
    {
        $message = new CreateArticleMessage($this->getData($request));

        /** @see Sulu\Bundle\ArticleBundle\Application\MessageHandler\CreateArticleMessageHandler */
        /** @var ArticleInterface $article */
        $article = $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        $uuid = $article->getUuid();

        $this->handleAction($request, $uuid);

        $response = $this->getAction($request, $uuid);

        return $response->setStatusCode(201);
    }

    public function putAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $message = new ModifyArticleMessage(['uuid' => $id], $this->getData($request));
        /** @see Sulu\Bundle\ArticleBundle\Application\MessageHandler\ModifyArticleMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        $this->handleAction($request, $id);

        return $this->getAction($request, $id);
    }

    public function postTriggerAction(Request $request, string $id): Response
    {
        $this->handleAction($request, $id);

        return $this->getAction($request, $id);
    }

    public function deleteAction(Request $request, string $id): Response // TODO route should be a uuid
    {
        $message = new RemoveArticleMessage(['uuid' => $id]);
        /** @see Sulu\Bundle\ArticleBundle\Application\MessageHandler\RemoveArticleMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return new Response('', 204);
    }

    /**
     * @return mixed[]
     */
    private function getData(Request $request): array
    {
        return \array_replace(
            $request->request->all(),
            [
                'locale' => $this->getLocale($request),
            ]
        );
    }

    private function getLocale(Request $request): string
    {
        return $request->query->getAlnum('locale', $request->getLocale());
    }

    private function handleAction(Request $request, string $uuid): ?ArticleInterface
    {
        $action = $request->query->get('action');

        if (!$action || 'draft' === $action) {
            return null;
        }

        if ('copy-locale' === $action) {
            $message = new CopyLocaleArticleMessage(
                ['uuid' => $uuid],
                (string) $request->query->get('src'),
                (string) $request->query->get('dest')
            );
            /** @see Sulu\Bundle\ArticleBundle\Application\MessageHandler\CopyLocaleArticleMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } else {
            $message = new ApplyWorkflowTransitionArticleMessage(['uuid' => $uuid], $this->getLocale($request), $action);
            /** @see Sulu\Bundle\ArticleBundle\Application\MessageHandler\ApplyWorkflowTransitionArticleMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }
    }
}
