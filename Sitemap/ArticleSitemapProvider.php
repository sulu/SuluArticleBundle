<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Sitemap;

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Index\DocumentFactoryInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;

/**
 * Integrates articles into sitemap.
 */
class ArticleSitemapProvider implements SitemapProviderInterface
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var DocumentFactoryInterface
     */
    private $documentFactory;

    /**
     * @param Manager $manager
     * @param DocumentFactoryInterface $documentFactory
     */
    public function __construct(Manager $manager, DocumentFactoryInterface $documentFactory)
    {
        $this->manager = $manager;
        $this->documentFactory = $documentFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function build($page, $portalKey, $locale)
    {
        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));

        $result = [];

        $from = 0;
        $size = 1000;
        do {
            $bulk = $this->getBulk($repository, $from, $size);

            /** @var ArticleViewDocumentInterface $item */
            foreach ($bulk as $item) {
                $result[] = new SitemapUrl($item->getRoutePath(), $item->getChanged());
            }
            $from += $size;
        } while ($bulk->count() > $from || $from > self::PAGE_SIZE);

        return $result;
    }

    private function getBulk(Repository $repository, $from, $size)
    {
        $search = $repository->createSearch()
            ->addQuery(new TermQuery('seo.hide_in_sitemap', 'false'))
            ->setFrom($from)
            ->setSize($size);

        return $repository->findDocuments($search);
    }

    /**
     * {@inheritdoc}
     */
    public function createSitemap($alias)
    {
        return new Sitemap($alias, $this->getMaxPage());
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPage()
    {
        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));
        $search = $repository->createSearch()
            ->addQuery(new TermQuery('seo.hide_in_sitemap', 'false'));

        return ceil($repository->count($search) / self::PAGE_SIZE);
    }
}
