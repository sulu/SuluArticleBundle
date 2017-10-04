<?php

namespace Sulu\Bundle\ArticleBundle\Elasticsearch;

use ONGR\ElasticsearchBundle\Service\Manager;
use Symfony\Component\EventDispatcher\Event;

/**
 * TODO add description here
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
     *
     * @return array
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * Returns document.
     *
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Returns manager.
     *
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
