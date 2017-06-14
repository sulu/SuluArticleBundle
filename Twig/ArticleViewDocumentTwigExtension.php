<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Twig;

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItem;
use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItemFactory;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Document\Repository\ArticleViewDocumentRepository;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extension for content form generation.
 */
class ArticleViewDocumentTwigExtension extends \Twig_Extension
{
    /**
     * @var ArticleViewDocumentRepository
     */
    protected $articleViewDocumentRepository;

    /**
     * @var ArticleResourceItemFactory
     */
    protected $articleResourceItemFactory;

    /**
     * @var ReferenceStoreInterface
     */
    protected $referenceStore;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param ArticleViewDocumentRepository $articleViewDocumentRepository
     * @param ArticleResourceItemFactory $articleResourceItemFactory
     * @param ReferenceStoreInterface $referenceStore
     * @param RequestStack $requestStack
     */
    public function __construct(
        ArticleViewDocumentRepository $articleViewDocumentRepository,
        ArticleResourceItemFactory $articleResourceItemFactory,
        ReferenceStoreInterface $referenceStore,
        RequestStack $requestStack
    ) {
        $this->articleViewDocumentRepository = $articleViewDocumentRepository;
        $this->articleResourceItemFactory = $articleResourceItemFactory;
        $this->referenceStore = $referenceStore;
        $this->requestStack = $requestStack;
    }

    /**
     * Returns an array of possible function in this extension.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_article_load_recent', [$this, 'loadRecent']),
            new \Twig_SimpleFunction('sulu_article_load_similar', [$this, 'loadSimilar']),
        ];
    }

    /**
     * Loads recent articles with given parameters.
     *
     * @param null|string $excludeUuid
     * @param bool $shouldExcludeUuid
     * @param null|array $types
     * @param null|string $locale
     * @param int $maxItems
     *
     * @return ArticleResourceItem[]
     */
    public function loadRecent(
        $excludeUuid = null,
        $shouldExcludeUuid = true,
        array $types = null,
        $locale = null,
        $maxItems = 5
    ) {
        if (!$locale || (!$excludeUuid && $shouldExcludeUuid) || !$types) {
            /** @var Request $request */
            $request = $this->requestStack->getCurrentRequest();
            $articleDocument = $request->get('object');
            if ($articleDocument instanceof ArticleDocument) {
                if (!$locale) {
                    $locale = $articleDocument->getLocale();
                }

                if (!$excludeUuid && $shouldExcludeUuid) {
                    $excludeUuid = $articleDocument->getUuid();
                }

                if (!$types) {
                    $types = [
                        $articleDocument->getStructureType(),
                    ];
                }
            }
        }

        $articleViewDocuments = $this->articleViewDocumentRepository->findRecent(
            $excludeUuid,
            $types,
            $locale,
            $maxItems
        );

        return $this->getResourceItems($articleViewDocuments);
    }

    /**
     * Loads similar articles with given parameters.
     *
     * @param null|string $uuid
     * @param null|array $types
     * @param null|string $locale
     * @param int $maxItems
     *
     * @return ArticleResourceItem[]
     */
    public function loadSimilar($uuid = null, array $types = null, $locale = null, $maxItems = 5)
    {
        if (!$locale || !$uuid || !$types) {
            $request = $this->requestStack->getCurrentRequest();
            $articleDocument = $request->get('object');
            if ($articleDocument instanceof ArticleDocument) {
                if (!$locale) {
                    $locale = $articleDocument->getLocale();
                }

                if (!$uuid) {
                    $uuid = $articleDocument->getUuid();
                }

                if (!$types) {
                    $types = [
                        $articleDocument->getStructureType(),
                    ];
                }
            }
        }

        $articleViewDocuments = $this->articleViewDocumentRepository->findSimilar($uuid, $types, $locale, $maxItems);

        return $this->getResourceItems($articleViewDocuments);
    }

    /**
     * @param DocumentIterator $articleViewDocuments
     *
     * @return ArticleResourceItem[]
     */
    private function getResourceItems(DocumentIterator $articleViewDocuments)
    {
        $articleResourceItems = [];

        /** @var ArticleViewDocument $articleViewDocument */
        foreach ($articleViewDocuments as $articleViewDocument) {
            $this->referenceStore->add($articleViewDocument->getUuid());
            $articleResourceItems[] = $this->articleResourceItemFactory->getResourceItem($articleViewDocument);
        }

        return $articleResourceItems;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_article.article_view_document';
    }
}
