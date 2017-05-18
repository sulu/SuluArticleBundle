<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Content;

use ONGR\ElasticsearchDSL\Query\TermLevel\PrefixQuery;
use ONGR\ElasticsearchDSL\Search;
use Sulu\Component\SmartContent\DatasourceItem;

/**
 * Introduces articles in smart-content and provides the page tree as data-source.
 */
class PageTreeArticleDataProvider extends ArticleDataProvider
{
    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->getConfigurationBuilder()
            ->enableDatasource(
                'content-datasource@sulucontent',
                [
                    'rootUrl' => '/admin/api/nodes?language={locale}&fields=title,order,published&webspace-nodes=all',
                    'selectedUrl' => '/admin/api/nodes/{datasource}?tree=true&language={locale}&fields=title,order,published&webspace-nodes=all',
                    'resultKey' => 'nodes',
                ]
            )
            ->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        if (!$datasource) {
            return;
        }

        $document = $this->documentManager->find($datasource, $options['locale']);

        return new DatasourceItem($document->getUuid(), $document->getTitle(), $document->getResourceSegment());
    }

    /**
     * {@inheritdoc}
     */
    protected function createSearch(Search $search, array $filters, $locale)
    {
        if (!array_key_exists('dataSource', $filters) || !$filters['dataSource']) {
            return;
        }

        $document = $this->documentManager->find($filters['dataSource'], $locale);
        if ($document) {
            // the selected data-source could be removed
            $search->addQuery(new PrefixQuery('route_path.raw', $document->getResourceSegment()));
        }

        return $search;
    }
}
