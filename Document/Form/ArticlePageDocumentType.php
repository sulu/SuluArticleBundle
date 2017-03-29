<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Form;

use Sulu\Bundle\ContentBundle\Form\Type\AbstractStructureBehaviorType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form-type for article-page mapping.
 */
class ArticlePageDocumentType extends AbstractStructureBehaviorType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_extra_fields' => true,
            ]
        );
    }
}
