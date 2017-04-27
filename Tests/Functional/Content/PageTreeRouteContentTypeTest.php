<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Content;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\Content\PageTreeRouteContentType;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Compat\PropertyInterface;

class PageTreeRouteContentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var PageTreeRouteContentType
     */
    private $contentType;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var string
     */
    private $propertyName = 'i18n:de-routePath';

    /**
     * @var string
     */
    private $webspaceKey = 'sulu_io';

    /**
     * @var string
     */
    private $locale = 'de';

    /**
     * @var string
     */
    private $template = 'test.html.twig';

    public function setUp()
    {
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);

        $this->property->getName()->willReturn($this->propertyName);
        $this->node->getPropertyValueWithDefault('i18n:' . $this->locale . '-title', null)->willReturn('Test article');
        $this->propertyEncoder->localizedContentName(Argument::type('string'), $this->locale)->will(
            function ($arguments) {
                return 'i18n:' . $arguments[1] . '-' . $arguments[0];
            }
        );

        $this->contentType = new PageTreeRouteContentType($this->template, $this->propertyEncoder->reveal());
    }

    public function testRead()
    {
        $value = [
            'path' => '/test-page/test-article',
            'suffix' => 'test-article',
            'page' => [
                'uuid' => '123-123-123',
                'path' => '/test-page',
            ],
        ];

        $this->node->getPropertyValueWithDefault($this->propertyName, '')->willReturn($value['path']);
        $this->node->getPropertyValueWithDefault($this->propertyName . '-suffix', '')->willReturn($value['suffix']);
        $this->node->hasProperty($this->propertyName . '-page')->willReturn(true);
        $this->node->getPropertyValue($this->propertyName . '-page', PropertyType::STRING)
            ->willReturn($value['page']['uuid']);
        $this->node->getPropertyValueWithDefault($this->propertyName . '-page-path', '')
            ->willReturn($value['page']['path']);

        $this->property->setValue($value)->shouldBeCalled();

        $result = $this->contentType->read(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            null
        );

        $this->assertEquals($value, $result);
    }

    public function testReadNotSet()
    {
        $value = [
            'path' => '/test-page/test-article',
            'suffix' => 'test-article',
            'page' => null,
        ];

        $this->node->getPropertyValueWithDefault($this->propertyName, '')->willReturn($value['path']);
        $this->node->getPropertyValueWithDefault($this->propertyName . '-suffix', '')->willReturn($value['suffix']);
        $this->node->hasProperty($this->propertyName . '-page')->willReturn(false);

        $this->property->setValue($value)->shouldBeCalled();

        $result = $this->contentType->read(
            $this->node->reveal(),
            $this->property->reveal(),
            $this->webspaceKey,
            $this->locale,
            null
        );

        $this->assertEquals($value, $result);
    }

    public function testWrite()
    {
        $value = [
            'path' => '/test-page/test-article',
            'suffix' => 'test-article',
            'page' => [
                'uuid' => '123-123-123',
                'path' => '/test-page',
            ],
        ];

        $this->property->getValue()->willReturn($value);

        $this->node->setProperty($this->propertyName, $value['path'])->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-suffix', $value['suffix'])->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page', $value['page']['uuid'], PropertyType::WEAKREFERENCE)
            ->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page-path', $value['page']['path'])->shouldBeCalled();

        $this->node->hasProperty($this->propertyName . '-page')->willReturn(false);

        $this->contentType->write(
            $this->node->reveal(),
            $this->property->reveal(),
            1,
            $this->webspaceKey,
            $this->locale,
            null
        );
    }

    public function testWriteExistingPageRelation()
    {
        $value = [
            'path' => '/test-page/test-article',
            'suffix' => 'test-article',
            'page' => [
                'uuid' => '123-123-123',
                'path' => '/test-page',
            ],
        ];

        $this->property->getValue()->willReturn($value);

        $this->node->setProperty($this->propertyName, $value['path'])->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-suffix', $value['suffix'])->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page', $value['page']['uuid'], PropertyType::WEAKREFERENCE)
            ->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page-path', $value['page']['path'])->shouldBeCalled();

        $pageProperty = $this->prophesize(\PHPCR\PropertyInterface::class);
        $pageProperty->remove()->shouldBeCalled();
        $this->node->hasProperty($this->propertyName . '-page')->willReturn(true);
        $this->node->getProperty($this->propertyName . '-page')->willReturn($pageProperty->reveal());

        $this->contentType->write(
            $this->node->reveal(),
            $this->property->reveal(),
            1,
            $this->webspaceKey,
            $this->locale,
            null
        );
    }

    public function testWriteGeneratePath()
    {
        $value = [
            'page' => [
                'uuid' => '123-123-123',
                'path' => '/test-page',
            ],
        ];

        $this->property->getValue()->willReturn($value);

        $this->node->setProperty($this->propertyName, '/test-page/test-article')->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-suffix', 'test-article')->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page', $value['page']['uuid'], PropertyType::WEAKREFERENCE)
            ->shouldBeCalled();
        $this->node->setProperty($this->propertyName . '-page-path', $value['page']['path'])->shouldBeCalled();

        $pageProperty = $this->prophesize(\PHPCR\PropertyInterface::class);
        $pageProperty->remove()->shouldBeCalled();
        $this->node->hasProperty($this->propertyName . '-page')->willReturn(true);
        $this->node->getProperty($this->propertyName . '-page')->willReturn($pageProperty->reveal());

        $this->contentType->write(
            $this->node->reveal(),
            $this->property->reveal(),
            1,
            $this->webspaceKey,
            $this->locale,
            null
        );
    }

    public function testGetTemplate()
    {
        $this->assertEquals($this->template, $this->contentType->getTemplate());
    }

    public function testGetContentData()
    {
        $value = [
            'page' => [
                'uuid' => '123-123-123',
                'path' => '/test-page',
            ],
            'path' => '/test-page/test-article',
            'suffix' => 'test-article',
        ];

        $this->property->getValue()->willReturn($value);

        $this->assertEquals($value['path'], $this->contentType->getContentData($this->property->reveal()));
    }

    public function testGetViewData()
    {
        $value = [
            'page' => [
                'uuid' => '123-123-123',
                'path' => '/test-page',
            ],
            'path' => '/test-page/test-article',
            'suffix' => 'test-article',
        ];

        $this->property->getValue()->willReturn($value);

        $this->assertEquals($value, $this->contentType->getViewData($this->property->reveal()));
    }
}
