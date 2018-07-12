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
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Index\DocumentFactoryInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapAlternateLink;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

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
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @param Manager $manager
     * @param DocumentFactoryInterface $documentFactory
     * @param WebspaceManagerInterface $webspaceManager
     */
    public function __construct(
        Manager $manager,
        DocumentFactoryInterface $documentFactory,
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->manager = $manager;
        $this->documentFactory = $documentFactory;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function build($page, $portalKey)
    {
        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));
        $portal = $this->webspaceManager->findPortalByKey($portalKey);

        if (!$portal) {
            throw new \InvalidArgumentException('Portal with key "' . $portalKey . '" not found');
        }

        $result = [];

        $from = 0;
        $size = 1000;
        do {
            $bulk = $this->getBulk($repository, $portal->getWebspace()->getKey(), $from, $size);
            /** @var SitemapUrl[] $alternatives */
            $sitemapUrlListByUuid = [];

            /** @var ArticleViewDocumentInterface $item */
            foreach ($bulk as $item) {
                $sitemapUrl = new SitemapUrl($item->getRoutePath(), $item->getLocale(), $item->getChanged());
                $result[] = $sitemapUrl;

                if (!isset($sitemapUrlListByUuid[$item->getUuid()])) {
                    $sitemapUrlListByUuid[$item->getUuid()] = [];
                }

                $sitemapUrlListByUuid[$item->getUuid()] = $this->setAlternatives(
                    $sitemapUrlListByUuid[$item->getUuid()],
                    $sitemapUrl
                );
            }

            $from += $size;
        } while ($bulk->count() > $from || $from > self::PAGE_SIZE);

        return $result;
    }

    /**
     * Set alternatives to sitemap url.
     *
     * @param SitemapUrl[] $sitemapUrlList
     * @param SitemapUrl $sitemapUrl
     *
     * @return SitemapUrl[]
     */
    private function setAlternatives(array $sitemapUrlList, SitemapUrl $sitemapUrl)
    {
        foreach ($sitemapUrlList as $sitemapUrlFromList) {
            // Add current as alternative to exist.
            $sitemapUrlFromList->addAlternateLink(
                new SitemapAlternateLink($sitemapUrl->getLoc(), $sitemapUrl->getLocale())
            );

            // Add others as alternative to current.
            $sitemapUrl->addAlternateLink(
                new SitemapAlternateLink($sitemapUrlFromList->getLoc(), $sitemapUrlFromList->getLocale())
            );
        }

        $sitemapUrlList[] = $sitemapUrl;

        return $sitemapUrlList;
    }

    private function getBulk(Repository $repository, $webspaceKey, $from, $size)
    {
        $search = $repository->createSearch()
            ->addQuery(new TermQuery('seo.hide_in_sitemap', 'false'))
            ->setFrom($from)
            ->setSize($size);

        $webspaceQuery = new BoolQuery();
        $webspaceQuery->add(new TermQuery('main_webspace', $webspaceKey), BoolQuery::SHOULD);
        $webspaceQuery->add(new TermQuery('additional_webspaces', $webspaceKey), BoolQuery::SHOULD);
        $search->addQuery($webspaceQuery);

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
