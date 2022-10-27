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

use Jackalope\Query\Row;
use PHPCR\Migrations\VersionInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Localization\Localization;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Version202210241106 implements VersionInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

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

    private function upgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();
        $localizations = $this->container->get('sulu_core.webspace.webspace_manager')->getAllLocalizations();

        /** @var Localization $localization */
        foreach ($localizations as $localization) {
            $suffixPropertyName = \sprintf('i18n:%s-routePath-suffix', $localization->getLocale());

            $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:article" OR [jcr:mixinTypes] = "sulu:articlepage")';
            $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

            /** @var Row<mixed> $row */
            foreach ($rows as $row) {
                $node = $row->getNode();

                if (!$node || !$node->hasProperty($suffixPropertyName)) {
                    continue;
                }

                $suffix = $node->getPropertyValue($suffixPropertyName);
                $newSuffix = '/' . \ltrim($suffix, '/');

                if ($suffix === $newSuffix) {
                    continue;
                }

                $node->setProperty($suffixPropertyName, $newSuffix);
            }
        }
    }

    private function downgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();
        $localizations = $this->container->get('sulu_core.webspace.webspace_manager')->getAllLocalizations();

        /** @var Localization $localization */
        foreach ($localizations as $localization) {
            $suffixPropertyName = \sprintf('i18n:%s-routePath-suffix', $localization->getLocale());

            $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:article" OR [jcr:mixinTypes] = "sulu:articlepage")';
            $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

            /** @var Row<mixed> $row */
            foreach ($rows as $row) {
                $node = $row->getNode();

                if (!$node || !$node->hasProperty($suffixPropertyName)) {
                    continue;
                }

                $suffix = $node->getPropertyValue($suffixPropertyName);
                $newSuffix = \ltrim($suffix, '/');

                if ($suffix === $newSuffix) {
                    continue;
                }

                $node->setProperty($suffixPropertyName, $newSuffix);
            }
        }
    }
}
