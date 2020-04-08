<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Elasticsearch;

use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Result\Converter;
use ONGR\ElasticsearchBundle\Service\Manager;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Extends converter of "ongr/elasticsearch-bundle" to throw event when converting array to document.
 */
class EventAwareConverter extends Converter
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(MetadataCollector $metadataCollector, EventDispatcherInterface $dispatcher)
    {
        parent::__construct($metadataCollector);

        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDocument($rawData, Manager $manager)
    {
        $document = parent::convertToDocument($rawData, $manager);
        $this->dispatcher->dispatch(new PostConvertToDocumentEvent($rawData, $document, $manager), PostConvertToDocumentEvent::NAME);

        return $document;
    }
}
