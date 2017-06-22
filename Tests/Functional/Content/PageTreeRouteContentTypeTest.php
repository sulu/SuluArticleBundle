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
use Sulu\Bundle\ArticleBundle\Content\PageTreeRouteContentType;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;

class PageTreeRouteContentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainRouteGenerator;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

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
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->chainRouteGenerator = $this->prophesize(ChainRouteGeneratorInterface::class);
        $this->conflictResolver = $this->prophesize(ConflictResolverInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);

        $this->property->getName()->willReturn($this->propertyName);

        $this->contentType = new PageTreeRouteContentType(
            $this->template,
            $this->documentRegistry->reveal(), $this->chainRouteGenerator->reveal(), $this->conflictResolver->reveal()
        );
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
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test-article');

        $route->setPath('/test-page/test-article')->shouldBeCalled()->will(
            function () use ($route) {
                $route->getPath()->willReturn('/test-page/test-article');
            }
        );

        $this->conflictResolver->resolve($route)->shouldBeCalled()->willReturn($route);

        $document = $this->prophesize(ArticleDocument::class);
        $this->chainRouteGenerator->generate($document->reveal())->willReturn($route->reveal());
        $this->documentRegistry->getDocumentForNode($this->node->reveal(), $this->locale)
            ->willReturn($document->reveal());

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

    public function testWriteGeneratePathRoot()
    {
        $route = $this->prophesize(RouteInterface::class);
        $route->getPath()->willReturn('/test-article');

        $route->setPath('/test-article')->shouldBeCalled()->will(
            function () use ($route) {
                $route->getPath()->willReturn('/test-article');
            }
        );

        $this->conflictResolver->resolve($route)->shouldBeCalled()->willReturn($route);

        $document = $this->prophesize(ArticleDocument::class);
        $this->chainRouteGenerator->generate($document->reveal())->willReturn($route->reveal());
        $this->documentRegistry->getDocumentForNode($this->node->reveal(), $this->locale)
            ->willReturn($document->reveal());

        $value = [
            'page' => [
                'uuid' => '123-123-123',
                'path' => '/',
            ],
        ];

        $this->property->getValue()->willReturn($value);

        $this->node->setProperty($this->propertyName, '/test-article')->shouldBeCalled();
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
