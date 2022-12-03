<?php

namespace Sulu\Bundle\ArticleBundle\Application\Message;

class CopyLocaleArticleMessage
{
    /**
     * @var array{
     *     uuid?: string
     * }
     */
    private $identifier;

    /**
     * @var string
     */
    private $sourceLocale;

    /**
     * @var string
     */
    private $targetLocale;

    /**
     * @param array{
     *     uuid?: string
     * } $identifier
     */
    public function __construct($identifier, string $sourceLocale, string $targetLocale)
    {
        $this->identifier = $identifier;
        $this->sourceLocale = $sourceLocale;
        $this->targetLocale = $targetLocale;
    }

    /**
     * @return array{
     *     uuid?: string
     * }
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getSourceLocale(): string
    {
        return $this->sourceLocale;
    }

    public function getTargetLocale(): string
    {
        return $this->targetLocale;
    }
}
