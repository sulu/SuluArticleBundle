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

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\IdsQuery;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * Provides article_selection content-type.
 */
class ArticleSelectionContentType extends SimpleContentType
{
    /**
     * @var Manager
     */
    private $searchManager;

    /**
     * @var string
     */
    private $articleDocumentClass;

    /**
     * @var string
     */
    private $template;

    /**
     * @param Manager $searchManager
     * @param string $articleDocumentClass
     * @param string $template
     */
    public function __construct(Manager $searchManager, $articleDocumentClass, $template)
    {
        parent::__construct('Article', []);

        $this->searchManager = $searchManager;
        $this->articleDocumentClass = $articleDocumentClass;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $value = $property->getValue();
        if ($value === null || !is_array($value) || count($value) === 0) {
            return [];
        }

        $repository = $this->searchManager->getRepository($this->articleDocumentClass);
        $search = $repository->createSearch();
        $search->addQuery(new IdsQuery($value));

        $result = [];
        /** @var ArticleViewDocumentInterface $articleDocument */
        foreach ($repository->execute($search) as $articleDocument) {
            $result[array_search($articleDocument->getUuid(), $value, false)] = $articleDocument;
        }

        ksort($result);

        return array_values($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
