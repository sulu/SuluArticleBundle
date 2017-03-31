<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Preview;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Integrates article items into preview-system.
 */
class ArticleObjectProvider implements PreviewObjectProviderInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param DocumentManagerInterface $documentManager
     * @param SerializerInterface $serializer
     */
    public function __construct(DocumentManagerInterface $documentManager, SerializerInterface $serializer)
    {
        $this->documentManager = $documentManager;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getObject($id, $locale)
    {
        return $this->documentManager->find(
            $id,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param ArticleDocument $object
     */
    public function getId($object)
    {
        return $object->getUuid();
    }

    /**
     * {@inheritdoc}
     *
     * @param ArticleDocument $object
     */
    public function setValues($object, $locale, array $data)
    {
        $propertyAccess = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();

        $structure = $object->getStructure();
        foreach ($data as $property => $value) {
            try {
                $propertyAccess->setValue($structure, $property, $value);
            } catch (\InvalidArgumentException $e) {
                //ignore not existing properties
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param ArticleDocument $object
     */
    public function setContext($object, $locale, array $context)
    {
        if (array_key_exists('template', $context)) {
            $object->setStructureType($context['template']);
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     *
     * @param ArticleDocument $object
     */
    public function serialize($object)
    {
        if ($object instanceof ArticlePageDocument) {
            // resolve proxy to ensure that this will also be serialized
            $object->getParent()->getTitle();
        }

        return $this->serializer->serialize(
            $object,
            'json',
            SerializationContext::create()->setSerializeNull(true)->setGroups(['preview'])
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param ArticleDocument $object
     */
    public function deserialize($serializedObject, $objectClass)
    {
        return $this->serializer->deserialize(
            $serializedObject,
            $objectClass,
            'json',
            DeserializationContext::create()->setSerializeNull(true)->setGroups(['preview'])
        );
    }
}
