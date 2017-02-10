<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use ONGR\ElasticsearchBundle\Annotation\Object;
use ONGR\ElasticsearchBundle\Annotation\Property;

/**
 * Contains localization state information for articles.
 *
 * @Object
 */
class LocalizationStateViewObject
{
    /**
     * @param string $state
     * @param string $locale
     */
    public function __construct(
        $state = null,
        $locale = null
    ) {
        $this->state = $state;
        $this->locale = $locale;
    }

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
     */
    public $state;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *        "fields"={
     *            "raw"={"type"="string", "index"="not_analyzed"},
     *            "value"={"type"="string"}
     *        }
     *    }
     * )
     */
    public $locale;
}
