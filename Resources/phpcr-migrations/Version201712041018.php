<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
 * Removes the property `sulu:author(ed)` and adds `i18n:<locale>-author(ed)`.
 */
class Version201712041018 implements VersionInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const AUTHOR_PROPERTY_NAME = 'sulu:author';

    const AUTHORED_PROPERTY_NAME = 'sulu:authored';

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
                if (!$node->hasProperty(sprintf('i18n:%s-template', $localization->getLocale()))) {
                    continue;
                }

                $this->upgradeAuthor($node, $localization->getLocale());
                $this->upgradeAuthored($node, $localization->getLocale());
            }

            if ($node->hasProperty(self::AUTHOR_PROPERTY_NAME)) {
                $node->getProperty(self::AUTHOR_PROPERTY_NAME)->remove();
            }
            if ($node->hasProperty(self::AUTHORED_PROPERTY_NAME)) {
                $node->getProperty(self::AUTHORED_PROPERTY_NAME)->remove();
            }
        }
    }

    private function upgradeAuthor(NodeInterface $node, $locale)
    {
        if (!$node->hasProperty(self::AUTHOR_PROPERTY_NAME)) {
            return;
        }

        $authorPropertyName = sprintf('i18n:%s-author', $locale);
        $node->setProperty($authorPropertyName, $node->getPropertyValue(self::AUTHOR_PROPERTY_NAME));
    }

    private function upgradeAuthored(NodeInterface $node, $locale)
    {
        if (!$node->hasProperty(self::AUTHORED_PROPERTY_NAME)) {
            return;
        }

        $authoredPropertyName = sprintf('i18n:%s-authored', $locale);
        $node->setProperty($authoredPropertyName, $node->getPropertyValue(self::AUTHORED_PROPERTY_NAME));
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
                if (!$node->hasProperty(sprintf('i18n:%s-changed', $localization->getLocale()))) {
                    continue;
                }

                $this->downgradeAuthor($node, $localization->getLocale());
                $this->downgradeAuthored($node, $localization->getLocale());
            }
        }
    }

    private function downgradeAuthor(NodeInterface $node, $locale)
    {
        $authorPropertyName = sprintf('i18n:%s-author', $locale);
        if (!$node->hasProperty($authorPropertyName)) {
            return;
        }

        $node->setProperty(self::AUTHOR_PROPERTY_NAME, $node->getPropertyValue($authorPropertyName));
        $node->getProperty($authorPropertyName)->remove();
    }

    private function downgradeAuthored(NodeInterface $node, $locale)
    {
        $authoredPropertyName = sprintf('i18n:%s-authored', $locale);
        if (!$node->hasProperty($authoredPropertyName)) {
            return;
        }

        $node->setProperty(self::AUTHORED_PROPERTY_NAME, $node->getPropertyValue($authoredPropertyName));
        $node->getProperty($authoredPropertyName)->remove();
    }
}
