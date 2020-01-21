<?php

declare(strict_types=1);

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
use PHPCR\SessionInterface;
use Sulu\Component\Localization\Localization;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Removes the property `i18n:<locale>-authors` and adds `i18n:<locale>-author`.
 */
class Version201702211450 implements VersionInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

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

    /**
     * {@inheritdoc}
     */
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
                $value = null;
                $authorsPropertyName = sprintf('i18n:%s-authors', $localization->getLocale());
                if ($node->hasProperty($authorsPropertyName)) {
                    $property = $node->getProperty($authorsPropertyName);
                    $value = $property->getValue();
                    if (is_array($value) && count($value) > 0) {
                        $value = $value[0];
                    }
                    $property->remove();
                }
                $authorPropertyName = sprintf('i18n:%s-author', $localization->getLocale());
                if (!$node->hasProperty($authorPropertyName)) {
                    $node->setProperty(
                        $authorPropertyName,
                        $value
                    );
                }
            }
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
            $node = $row->getNode();

            /** @var Localization $localization */
            foreach ($localizations as $localization) {
                $value = null;
                $authorPropertyName = sprintf('i18n:%s-author', $localization->getLocale());
                if ($node->hasProperty($authorPropertyName)) {
                    $property = $node->getProperty($authorPropertyName);
                    $value = $property->getValue();
                    $property->remove();
                }
                $authorsPropertyName = sprintf('i18n:%s-authors', $localization->getLocale());
                if (!$node->hasProperty($authorsPropertyName)) {
                    $node->setProperty(
                        $authorsPropertyName,
                        [$value]
                    );
                }
            }
        }
    }
}
