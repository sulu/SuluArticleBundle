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

namespace Sulu\Bundle\ArticleBundle\Document\Form\Listener;

use Symfony\Component\Form\FormEvent;

/**
 * Normalizes the legacy Sulu request data.
 * Listens to the form framework on the PRE_SUBMIT event.
 */
class DataNormalizer
{
    /**
     * Normalize incoming data from the legacy node controller.
     */
    public static function normalize(FormEvent $event)
    {
        $data = $event->getData();
        $data = array_merge(
            [
                'mainWebspace' => self::getAndUnsetValue($data['structure'], 'mainWebspace'),
                'additionalWebspaces' => self::getAndUnsetValue($data['structure'], 'additionalWebspaces'),
            ],
            $data
        );

        $event->setData($data);
    }

    /**
     * @return mixed|null
     */
    private static function getAndUnsetValue(array &$data, string $key)
    {
        $value = null;

        if (isset($data[$key])) {
            $value = $data[$key];
            unset($data[$key]);
        }

        return $value;
    }
}
