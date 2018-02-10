<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Resolver;

use Prooph\EventSourcing\AggregateChanged;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Article;

class EventResolverPool
{
    /**
     * @var EventResolverInterface[][]
     */
    private $resolvers = [];

    /**
     * @var EventResolverInterface[][]
     */
    private $sorted = [];

    public function addEventResolver(EventResolverInterface $resolver)
    {
        foreach ($resolver->getResolvingEvents() as $evenClass => $params) {
            if (is_string($params) || is_string($params[0])) {
                $this->registerEventResolver($resolver, $evenClass, $params);

                continue;
            }

            foreach ($params as $listener) {
                $this->registerEventResolver($resolver, $evenClass, $listener);
            }
        }
    }

    private function registerEventResolver(EventResolverInterface $resolver, string $eventClass, $params)
    {
        $callable = [$resolver];
        $priority = 0;

        if (is_string($params)) {
            $callable[1] = $params;
            $this->resolvers[$eventClass][] = [$resolver, $params, 0];
        } elseif (is_string($params[0])) {
            $callable[1] = $params[0];
            $priority = array_key_exists(1, $params) ? $params[1] : 0;

        }

        $this->resolvers[$eventClass][$priority][] = $callable;
    }

    private function getResolver(string $eventClass): array
    {
        if (array_key_exists($eventClass, $this->sorted)) {
            return $this->sorted[$eventClass];
        }

        krsort($this->resolvers[$eventClass]);
        $this->sorted[$eventClass] = [];

        foreach ($this->resolvers[$eventClass] as $resolvers) {
            $this->sorted[$eventClass] = array_merge($this->sorted[$eventClass], $resolvers);
        }

        return $this->sorted[$eventClass];
    }

    public function resolve(Article $article, AggregateChanged $event): bool
    {
        $resolvers = $this->getResolver(get_class($event));
        if (0 === count($resolvers)) {
            return false;
        }

        foreach ($resolvers as $resolver) {
            call_user_func_array($resolver, [$article, $event]);
        }

        return true;
    }
}
