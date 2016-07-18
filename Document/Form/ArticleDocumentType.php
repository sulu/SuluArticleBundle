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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form-type for article mapping.
 */
class ArticleDocumentType extends AbstractStructureBehaviorType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // extensions
        $builder->add('extensions', TextType::class, ['property_path' => 'extensionsData']);

        // TODO: Fix the admin interface to not send this junk (not required for articles)
        $builder->add('redirectType', TextType::class, ['mapped' => false]);
        $builder->add('resourceSegment', TextType::class, ['mapped' => false]);
        $builder->add('navigationContexts', TextType::class, ['mapped' => false]);
        $builder->add('shadowLocaleEnabled', TextType::class, ['mapped' => false]);
    }
}
