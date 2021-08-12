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
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
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

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var DocumentInspector|null
     */
    private $documentInspector;

    public function __construct(
        RequestStack $requestStack,
        RouteRepositoryInterface $routeRepository,
        WebspaceManagerInterface $webspaceManager,
        DocumentInspector $documentInspector = null
    ) {
        $this->requestStack = $requestStack;
        $this->routeRepository = $routeRepository;
        $this->webspaceManager = $webspaceManager;
        $this->documentInspector = $documentInspector;

        if (null === $this->documentInspector) {
            @\trigger_error(
                'Instantiating the WebsiteArticleUrlsSubscriber without the $documentInspector argument is deprecated!',
                \E_USER_DEPRECATED
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'addUrlsOnPostSerialize',
            ],
        ];
    }

    /**
     * Loops thru current webspace locales and generates routes for them.
     */
    public function addUrlsOnPostSerialize(ObjectEvent $event): void
    {
        $article = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        $request = $this->requestStack->getCurrentRequest();

        if (!$article instanceof ArticleDocument || !$context->hasAttribute('urls') || !$request) {
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
        $localizations = [];
        $publishedLocales = $this->getPublishedLocales($article, $webspace);

        foreach ($this->getWebspaceLocales($webspace) as $locale) {
            $published = in_array($locale, $publishedLocales, true);
            $path = '/';
            $alternate = false;

            if ($published) {
                $route = $this->routeRepository->findByEntity(get_class($article), $article->getUuid(), $locale);

                if ($route) {
                    $path = $route->getPath();
                    $alternate = true;
                }
            }

            $urls[$locale] = $path;
            $localizations[$locale] = [
                'locale' => $locale,
                'url' => $this->webspaceManager->findUrlByResourceLocator($path, null, $locale),
                'alternate' => $alternate,
            ];
        }

        $visitor->visitProperty(new StaticPropertyMetadata('', 'urls', $urls), $urls);
        $visitor->visitProperty(new StaticPropertyMetadata('', 'localizations', $localizations), $localizations);
    }

    /**
     * @return string[]
     */
    private function getWebspaceLocales(Webspace $webspace): array
    {
        return \array_map(
            function(Localization $localization) {
                return $localization->getLocale();
            },
            $webspace->getAllLocalizations()
        );
    }

    /**
     * @return string[]
     */
    private function getPublishedLocales(ArticleDocument $article, Webspace $webspace): array
    {
        if (null === $this->documentInspector) {
            // BC layer
            return $this->getWebspaceLocales($webspace);
        }

        return $this->documentInspector->getPublishedLocales($article);
    }
}
