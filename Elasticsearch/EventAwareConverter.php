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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Extends converter of "ongr/elasticsearch-bundle" to throw event when converting array to document.
 */
class EventAwareConverter extends Converter
{
    const EVENT_POST_CONVERT_TO_DOCUMENT = 'es.post_convert_to_document';

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
        $this->dispatcher->dispatch(
            self::EVENT_POST_CONVERT_TO_DOCUMENT,
            new PostConvertToDocumentEvent($rawData, $document, $manager)
        );

        return $document;
    }
}
