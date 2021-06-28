<?php

namespace Sulu\Bundle\ArticleBundle\UserInterface\Controller\Admin;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\ControllerTrait;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ArticleBundle\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @final Use request or response listener to add custom logic for a controller.
 */
class ArticleController implements ClassResourceInterface
{
    use ControllerTrait;

    /**
     * @var ArticleRepositoryInterface
     */
    private $articleRepository;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        ViewHandlerInterface $viewhandler
    ) {
        $this->articleRepository = $articleRepository;
        $this->setViewHandler($viewhandler);
    }

    public function cgetAction(Request $request): Response
    {
        return new Response(501);
    }

    public function getAction(Request $request, string $uuid): Response
    {
        $locale = $request->query->get('locale', $request->getLocale());

        $article = $this->articleRepository->getOneBy([
            'uuid' => $uuid,
            'locale' => $locale,
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ], [
            'context' => 'article_admin',
        ]);

        return $this->handleView(
            $this->view($article, 200)
                ->setContext((new Context())->setSerializeNull(null)->setGroups(['article_admin']))
        );
    }

    public function postAction(Request $request): Response
    {
        return new Response(501);
    }

    public function putAction(Request $request, string $uuid): Response
    {
        return new Response(501);
    }

    public function deleteAction(Request $request, string $uuid): Response
    {
        return new Response(501);
    }
}
