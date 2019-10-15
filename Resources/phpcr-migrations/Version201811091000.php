<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle;

use Jackalope\Node;
use Jackalope\Query\Row;
use PHPCR\Migrations\VersionInterface;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Localization\Localization;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Removes the property `mainWebspace` and adds `i18n:<locale>-mainWebspace`.
 * Removes the property `additionalWebspaces` and adds `i18n:<locale>-additionalWebspaces`.
 */
class Version201811091000 implements VersionInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const MAIN_WEBSPACE_PROPERTY_NAME = 'mainWebspace';

    const ADDITONAL_WEBSPACES_PROPERTY_NAME = 'additionalWebspaces';

    /**
     * {@inheritdoc}
     */
    public function up(SessionInterface $session)
    {
        $liveSession = $this->container->get('sulu_document_manager.live_session');

        $this->upgrade($liveSession);
        $this->upgrade($session);

        $liveSession->save();
        $session->save();
    }

    public function down(SessionInterface $session)
    {
        $liveSession = $this->container->get('sulu_document_manager.live_session');

        $this->downgrade($liveSession);
        $this->downgrade($session);

        $liveSession->save();
        $session->save();
    }

    /**
     * Upgrade all nodes in given session.
     */
    private function upgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();
        $localizations = $this->container->get('sulu_core.webspace.webspace_manager')->getAllLocalizations();

        $query = 'SELECT * FROM [nt:unstructured] WHERE [jcr:mixinTypes] = "sulu:article"';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        /** @var Row $row */
        foreach ($rows as $row) {
            /** @var Node $node */
            $node = $row->getNode();

            /** @var Localization $localization */
            foreach ($localizations as $localization) {
                // check if node exists
                if (!$node->hasProperty(sprintf('i18n:%s-template', $localization->getLocale()))) {
                    continue;
                }

                $this->upgradeMainWebspace($node, $localization->getLocale());
                $this->upgradeAdditionalWebspaces($node, $localization->getLocale());
            }

            if ($node->hasProperty(self::MAIN_WEBSPACE_PROPERTY_NAME)) {
                $node->getProperty(self::MAIN_WEBSPACE_PROPERTY_NAME)->remove();
            }
            if ($node->hasProperty(self::ADDITONAL_WEBSPACES_PROPERTY_NAME)) {
                $node->getProperty(self::ADDITONAL_WEBSPACES_PROPERTY_NAME)->remove();
            }
        }
    }

    private function upgradeMainWebspace(NodeInterface $node, $locale)
    {
        if (!$node->hasProperty(self::MAIN_WEBSPACE_PROPERTY_NAME)) {
            return;
        }

        $value = $node->getPropertyValue(self::MAIN_WEBSPACE_PROPERTY_NAME);
        if ($value) {
            $mainWebspacePropertyNameLocalized = sprintf('i18n:%s-' . self::MAIN_WEBSPACE_PROPERTY_NAME, $locale);
            $node->setProperty(
                $mainWebspacePropertyNameLocalized,
                $node->getPropertyValue(self::MAIN_WEBSPACE_PROPERTY_NAME)
            );
        }
    }

    private function upgradeAdditionalWebspaces(NodeInterface $node, $locale)
    {
        if (!$node->hasProperty(self::ADDITONAL_WEBSPACES_PROPERTY_NAME)) {
            return;
        }

        $value = $node->getPropertyValue(self::ADDITONAL_WEBSPACES_PROPERTY_NAME);
        if ($value) {
            $additionalWebspacesPropertyNameLocalized = sprintf('i18n:%s-' . self::ADDITONAL_WEBSPACES_PROPERTY_NAME, $locale);
            $node->setProperty(
                $additionalWebspacesPropertyNameLocalized,
                $value
            );
        }
    }

    /**
     * Downgrades all nodes in given session.
     */
    private function downgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();
        $localizations = $this->container->get('sulu_core.webspace.webspace_manager')->getAllLocalizations();

        $query = 'SELECT * FROM [nt:unstructured] WHERE [jcr:mixinTypes] = "sulu:article"';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        /** @var Row $row */
        foreach ($rows as $row) {
            /** @var Node $node */
            $node = $row->getNode();

            /** @var Localization $localization */
            foreach ($localizations as $localization) {
                // check if node exists
                if (!$node->hasProperty(sprintf('i18n:%s-template', $localization->getLocale()))) {
                    continue;
                }

                $this->downgradeMainWebspace($node, $localization->getLocale());
                $this->downgradeAdditionalWebspaces($node, $localization->getLocale());
            }
        }
    }

    private function downgradeMainWebspace(NodeInterface $node, $locale)
    {
        $mainWebspacePropertyNameLocalized = sprintf('i18n:%s-' . self::MAIN_WEBSPACE_PROPERTY_NAME, $locale);
        if (!$node->hasProperty($mainWebspacePropertyNameLocalized)) {
            return;
        }

        $value = $node->getPropertyValue($mainWebspacePropertyNameLocalized);
        if ($value) {
            $node->setProperty(self::MAIN_WEBSPACE_PROPERTY_NAME, $value);
        }

        $node->getProperty($mainWebspacePropertyNameLocalized)->remove();
    }

    private function downgradeAdditionalWebspaces(NodeInterface $node, $locale)
    {
        $additionalWebspacesPropertyNameLocalized = sprintf('i18n:%s-' . self::ADDITONAL_WEBSPACES_PROPERTY_NAME, $locale);
        if (!$node->hasProperty($additionalWebspacesPropertyNameLocalized)) {
            return;
        }

        $value = $node->getPropertyValue($additionalWebspacesPropertyNameLocalized);
        if ($value) {
            $node->setProperty(self::ADDITONAL_WEBSPACES_PROPERTY_NAME, $value);
        }

        $node->getProperty($additionalWebspacesPropertyNameLocalized)->remove();
    }
}
