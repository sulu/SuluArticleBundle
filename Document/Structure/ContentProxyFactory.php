<?php

namespace Sulu\Bundle\ArticleBundle\Document\Structure;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;

/**
 * TODO add description here
 */
class ContentProxyFactory
{
    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param LazyLoadingValueHolderFactory $proxyFactory
     */
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        LazyLoadingValueHolderFactory $proxyFactory
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->proxyFactory = $proxyFactory;
    }

    /**
     * Create content-proxy for given structure.
     *
     * @param StructureInterface $structure
     * @param array $data
     *
     * @return array
     */
    public function createContentProxy(StructureInterface $structure, array $data)
    {
        return $this->proxyFactory->createProxy(
            \ArrayObject::class,
            function (
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($structure, $data) {
                $content = [];
                foreach ($structure->getProperties(true) as $child) {
                    if (array_key_exists($child->getName(), $data)) {
                        $child->setValue($data[$child->getName()]);
                    }

                    $contentType = $this->contentTypeManager->get($child->getContentTypeName());
                    $content[$child->getName()] = $contentType->getContentData($child);
                }

                $initializer = null;
                $wrappedObject = new \ArrayObject($content);

                return true;
            }
        );
    }

    /**
     * Create view-proxy for given structure.
     *
     * @param StructureInterface $structure
     * @param array $data
     *
     * @return array
     */
    public function createViewProxy(StructureInterface $structure, array $data)
    {
        return $this->proxyFactory->createProxy(
            \ArrayObject::class,
            function (
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($structure, $data) {
                $view = [];
                foreach ($structure->getProperties(true) as $child) {
                    if (array_key_exists($child->getName(), $data)) {
                        $child->setValue($data[$child->getName()]);
                    }

                    $contentType = $this->contentTypeManager->get($child->getContentTypeName());
                    $view[$child->getName()] = $contentType->getViewData($child);
                }

                $initializer = null;
                $wrappedObject = new \ArrayObject($view);

                return true;
            }
        );
    }
}
