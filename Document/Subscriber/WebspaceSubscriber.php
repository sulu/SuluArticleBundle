<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Subscriber;

use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\Behavior\WebspaceBehavior;
use Sulu\Bundle\ArticleBundle\Metadata\PageTreeTrait;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events to set webspace settings.
 */
class WebspaceSubscriber implements EventSubscriberInterface
{
    use PageTreeTrait;

    const MAIN_WEBSPACE_PROPERTY = 'mainWebspace';

    const ADDITIONAL_WEBSPACES_PROPERTY = 'additionalWebspaces';

    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $structureMetadataFactory;

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var DocumentInspector
     */
    protected $documentInspector;

    /**
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     */
    public function __construct(
        StructureMetadataFactoryInterface $structureMetadataFactory,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
    }

    /**
     * {@inheritdoc}
     */
    protected function getStructureMetadataFactory()
    {
        return $this->structureMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => ['loadProperties'],
            Events::PERSIST => ['saveProperties'],
            Events::PUBLISH => ['saveProperties'],
        ];
    }

    /**
     * @param AbstractMappingEvent $event
     */
    public function loadProperties(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof WebspaceBehavior) {
            return;
        }

        $mainWebspace = $event->getNode()->getPropertyValueWithDefault(self::MAIN_WEBSPACE_PROPERTY, null);
        $additionalWebspaces = $event->getNode()->getPropertyValueWithDefault(self::ADDITIONAL_WEBSPACES_PROPERTY, null);

        $document->setMainWebspace($mainWebspace);
        $document->setAdditionalWebspaces($additionalWebspaces);
    }

    /**
     * @param AbstractMappingEvent $event
     */
    public function saveProperties(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleInterface || !$document instanceof WebspaceBehavior) {
            return;
        }

        $parentPageUuid = $this->getParentPageUuidFromPageTree($document);
        if ($parentPageUuid) {
            // we know now it's a `page_tree_route` route
            // so load the parent and find out the webspace
            $parentDocument = $this->documentManager->find($parentPageUuid, $event->getLocale());
            $mainWebspace = $this->documentInspector->getWebspace($parentDocument);
            $document->setMainWebspace($mainWebspace);
            $document->setAdditionalWebspaces([]);
        }

        $mainWebspace = $document->getMainWebspace();
        $additionalWebspaces = null;
        if ($mainWebspace) {
            $mainWebspace = $document->getMainWebspace();
            $additionalWebspaces = $document->getAdditionalWebspaces();
        }

        $event->getNode()->setProperty(self::MAIN_WEBSPACE_PROPERTY, $mainWebspace);
        $event->getNode()->setProperty(self::ADDITIONAL_WEBSPACES_PROPERTY, $additionalWebspaces);
    }
}
