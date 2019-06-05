<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Markup;

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Search;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Integrates articles into link-system.
 */
class ArticleLinkProvider implements LinkProviderInterface
{
    use ArticleViewDocumentIdTrait;

    /**
     * @var Manager
     */
    private $liveManager;

    /**
     * @var Manager
     */
    private $defaultManager;

    /**
     * @var WebspaceManagerInterface
     */
    protected $webspaceManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $types;

    /**
     * @var string
     */
    private $articleViewClass;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        Manager $liveManager,
        Manager $defaultManager,
        WebspaceManagerInterface $webspaceManager,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        array $types,
        $articleViewClass,
        $environment
    ) {
        $this->liveManager = $liveManager;
        $this->defaultManager = $defaultManager;
        $this->webspaceManager = $webspaceManager;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->types = $types;
        $this->articleViewClass = $articleViewClass;
        $this->environment = $environment;
    }

    public function getConfiguration()
    {
        // TODO implement tabs again?

        return LinkConfigurationBuilder::create()
            ->setTitle($this->translator->trans('sulu_article.articles', [], 'admin'))
            ->setResourceKey('articles')
            ->setListAdapter('table')
            ->setDisplayProperties(['title'])
            ->setOverlayTitle($this->translator->trans('sulu_article.single_selection_overlay_title', [], 'admin'))
            ->setEmptyText($this->translator->trans('sulu_article.no_article_selected', [], 'admin'))
            ->setIcon('su-newspaper')
            ->getLinkConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function preload(array $hrefs, $locale, $published = true)
    {
        $request = $this->requestStack->getCurrentRequest();

        $scheme = 'http';
        if ($request) {
            $scheme = $request->getScheme();
        }

        $search = new Search();
        $search->addQuery(new IdsQuery($this->getViewDocumentIds($hrefs, $locale)));
        $search->setSize(count($hrefs));

        $repository = $this->liveManager->getRepository($this->articleViewClass);
        if (!$published) {
            $repository = $this->defaultManager->getRepository($this->articleViewClass);
        }

        $documents = $repository->findDocuments($search);

        $result = [];
        /** @var ArticleViewDocumentInterface $document */
        foreach ($documents as $document) {
            $result[] = $this->createLinkItem($document, $locale, $scheme);
        }

        return $result;
    }

    /**
     * @param ArticleViewDocumentInterface $document
     * @param string $locale
     * @param string $scheme
     *
     * @return LinkItem
     */
    protected function createLinkItem(ArticleViewDocumentInterface $document, $locale, $scheme)
    {
        $url = $this->webspaceManager->findUrlByResourceLocator(
            $document->getRoutePath(),
            $this->environment,
            $locale,
            $document->getTargetWebspace(),
            null,
            $scheme
        );

        return new LinkItem($document->getUuid(), $document->getTitle(), $url, $document->getPublishedState());
    }
}
