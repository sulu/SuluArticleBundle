<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Add the urls to the serialized result.
 */
class WebsiteArticleUrlsSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    public function __construct(RequestStack $requestStack, RouteRepositoryInterface $routeRepository)
    {
        $this->requestStack = $requestStack;
        $this->routeRepository = $routeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'array',
                'method' => 'addUrlsOnPostSerialize',
            ],
        ];
    }

    /**
     * Loops thru current webspace locales and generates routes for them.
     */
    public function addUrlsOnPostSerialize(ObjectEvent $event)
    {
        $article = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        $request = $this->requestStack->getCurrentRequest();

        if (!$article instanceof ArticleDocument || !$context->hasAttribute('website') || !$request) {
            return;
        }

        /** @var RequestAttributes $attributes */
        $attributes = $request->get('_sulu');
        if (!$attributes) {
            return;
        }

        /** @var Webspace $webspace */
        $webspace = $attributes->getAttribute('webspace');
        if (!$webspace) {
            return;
        }

        $urls = [];
        foreach ($webspace->getAllLocalizations() as $localization) {
            $locale = $localization->getLocale();
            $route = $this->routeRepository->findByEntity(get_class($article), $article->getUuid(), $locale);

            $urls[$locale] = '/';
            if ($route) {
                $urls[$locale] = $route->getPath();
            }
        }

        $visitor->visitProperty(new StaticPropertyMetadata('', 'urls', $urls), $urls);
    }
}
