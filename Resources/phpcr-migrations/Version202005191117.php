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

use PHPCR\Migrations\VersionInterface;
use PHPCR\SessionInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Version202005191117 implements VersionInterface, ContainerAwareInterface
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

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:snippet")';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        foreach ($rows as $row) {
            $node = $row->getNode();

            foreach ($node->getProperties() as $property) {
                if (\is_string($property->getValue())) {
                    $propertyValue = json_decode($property->getValue(), true);
                    if (\is_array($propertyValue) && \array_key_exists('items', $propertyValue)) {
                        foreach ($propertyValue['items'] as &$item) {
                            if (isset($item['type']) && 'article' === $item['type']) {
                                $item['type'] = 'articles';
                            }
                        }

                        $property->setValue(json_encode($propertyValue));
                    }
                }
            }
        }
    }

    private function downgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:snippet")';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        foreach ($rows as $row) {
            $node = $row->getNode();

            foreach ($node->getProperties() as $property) {
                if (\is_string($property->getValue())) {
                    $propertyValue = json_decode($property->getValue(), true);
                    if (\is_array($propertyValue) && \array_key_exists('items', $propertyValue)) {
                        foreach ($propertyValue['items'] as &$item) {
                            if (isset($item['type']) && 'articles' === $item['type']) {
                                $item['type'] = 'article';
                            }
                        }

                        $property->setValue(json_encode($propertyValue));
                    }
                }
            }
        }
    }
}
