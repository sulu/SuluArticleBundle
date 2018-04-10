<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Factory;

use Sulu\Bundle\ArticleBundle\ListBuilder\ElasticSearchFieldDescriptor;
use Sulu\Bundle\ArticleBundle\ListBuilder\ElasticSearchFieldDescriptorBuilder;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

class ElasticSearchFieldDescriptorBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testStaticFactory()
    {
        $builder = ElasticSearchFieldDescriptor::create('title', 'public.title');

        $fieldDescriptor = $builder->build();
        $this->assertInstanceOf(ElasticSearchFieldDescriptor::class, $fieldDescriptor);
        $this->assertEquals('title', $fieldDescriptor->getName());
        $this->assertEquals('public.title', $fieldDescriptor->getTranslation());
        $this->assertFalse($fieldDescriptor->getSortable());
        $this->assertFalse($fieldDescriptor->getDisabled());
        $this->assertFalse($fieldDescriptor->getEditable());
        $this->assertFalse($fieldDescriptor->getDefault());
        $this->assertEquals('string', $fieldDescriptor->getType());
    }

    public function testConstructor()
    {
        $builder = new ElasticSearchFieldDescriptorBuilder('title', 'public.title');

        $fieldDescriptor = $builder->build();
        $this->assertInstanceOf(ElasticSearchFieldDescriptor::class, $fieldDescriptor);
        $this->assertEquals('title', $fieldDescriptor->getName());
        $this->assertEquals('public.title', $fieldDescriptor->getTranslation());
    }

    public function testSetVisibility()
    {
        $builder = new ElasticSearchFieldDescriptorBuilder('title', 'public.title');

        $builder->setVisibility(FieldDescriptorInterface::VISIBILITY_ALWAYS);

        $fieldDescriptor = $builder->build();
        $this->assertSame(FieldDescriptorInterface::VISIBILITY_ALWAYS, $fieldDescriptor->getVisibility());
    }

    public function testSetSortField()
    {
        $builder = new ElasticSearchFieldDescriptorBuilder('title', 'public.title');

        $builder->setSortField('title.raw');

        $fieldDescriptor = $builder->build();
        $this->assertEquals('title.raw', $fieldDescriptor->getSortField());
        $this->assertTrue($fieldDescriptor->getSortable());
    }

    public function testSetType()
    {
        $builder = new ElasticSearchFieldDescriptorBuilder('title', 'public.title');

        $builder->setType('boolean');

        $fieldDescriptor = $builder->build();
        $this->assertEquals('boolean', $fieldDescriptor->getType());
    }
}
