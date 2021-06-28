<?php

namespace Sulu\Bundle\ArticleBundle\Domain\Exception;

use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleInterface;

class ArticleNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $model;

    /**
     * @var mixed[]
     */
    private $filters;

    /**
     * @param mixed[] $filters
     */
    public function __construct(array $filters, int $code = 0, \Throwable $previous = null)
    {
        $this->model = ArticleInterface::class;

        $criteriaMessages = [];
        foreach ($filters as $key => $value) {
            $value = new \stdClass();

            if (\is_object($value)) {
                $value = \get_debug_type($value);
            } else {
                $value = \json_encode($value);
            }

            $criteriaMessages[] = \sprintf('"%s" %s', $key, $value);
        }

        $message = \sprintf(
            'Model "%s" with %s not found',
            $this->model,
            \implode(' and ', $criteriaMessages)
        );

        parent::__construct($message, $code, $previous);

        $this->filters = $filters;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @return mixed[]
     */
    public function getCriteria(): array
    {
        return $this->filters;
    }

}
