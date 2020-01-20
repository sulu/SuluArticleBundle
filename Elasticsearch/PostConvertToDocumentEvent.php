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

use ONGR\ElasticsearchBundle\Service\Manager;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event args for "es.post_convert_to_document".
 */
class PostConvertToDocumentEvent extends Event
{
    /**
     * @var array
     */
    private $rawData;

    /**
     * @var object
     */
    private $document;

    /**
     * @var Manager
     */
    private $manager;

    public function __construct(array $rawData, $document, Manager $manager)
    {
        $this->rawData = $rawData;
        $this->document = $document;
        $this->manager = $manager;
    }

    /**
     * Returns rawData.
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * Returns document.
     */
    public function getDocument(): object
    {
        return $this->document;
    }

    /**
     * Returns manager.
     */
    public function getManager(): Manager
    {
        return $this->manager;
    }
}
