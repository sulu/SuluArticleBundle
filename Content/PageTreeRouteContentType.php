<?php

namespace Sulu\Bundle\ArticleBundle\Content;

use Sulu\Component\Content\SimpleContentType;

/**
 * TODO add description here
 */
class PageTreeRouteContentType extends SimpleContentType
{
    /**
     * @var string
     */
    private $template;

    /**
     * @param string $template
     */
    public function __construct($template)
    {
        parent::__construct('PageTreeRoute', ['uuid' => null, 'path' => '']);

        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    protected function encodeValue($value)
    {
        return json_encode(parent::encodeValue($value));
    }

    /**
     * {@inheritdoc}
     */
    protected function decodeValue($value)
    {
        return json_decode(parent::decodeValue($value));
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
