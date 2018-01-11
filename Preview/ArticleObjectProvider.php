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
     * @var string
     */
    private $articleDocumentClass;

    /**
     * @param DocumentManagerInterface $documentManager
     * @param SerializerInterface $serializer
     * @param $articleDocumentClass
     */
    public function __construct(
        DocumentManagerInterface $documentManager,
        SerializerInterface $serializer,
        $articleDocumentClass
    ) {
        $this->documentManager = $documentManager;
        $this->serializer = $serializer;
        $this->articleDocumentClass = $articleDocumentClass;
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
        $pageNumber = $object->getPageNumber();
        if ($object instanceof ArticlePageDocument) {
            // resolve proxy to ensure that this will also be serialized
            $object = $object->getParent();

            $object->getTitle();
        }

        $result = $this->serializer->serialize(
            $object,
            'json',
            SerializationContext::create()
                ->setSerializeNull(true)
                ->setGroups(['preview'])
        );

        return json_encode(['pageNumber' => $pageNumber, 'object' => $result]);
    }

    /**
     * {@inheritdoc}
     *
     * @param ArticleDocument $object
     */
    public function deserialize($serializedObject, $objectClass)
    {
        $result = json_decode($serializedObject, true);

        $article = $this->serializer->deserialize(
            $result['object'],
            $this->articleDocumentClass,
            'json',
            DeserializationContext::create()
                ->setSerializeNull(true)
                ->setGroups(['preview'])
        );

        foreach ($article->getChildren() as $child) {
            $child->setParent($article);
        }

        if (1 === $result['pageNumber']) {
            return $article;
        }

        $children = array_values($article->getChildren());

        $object = $children[$result['pageNumber'] - 2];

        return $object;
    }
}
