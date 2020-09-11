<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Elasticsearch;

use Doctrine\Common\Annotations\Reader;
use ONGR\ElasticsearchBundle\Annotation\Document;

class AnnotationReader implements Reader
{
    /**
     * @var Reader
     */
    private $inner;

    /**
     * @var string
     */
    private $articleViewDocumentClass;

    public function __construct(Reader $inner, $articleViewDocumentClass)
    {
        $this->inner = $inner;
        $this->articleViewDocumentClass = $articleViewDocumentClass;
    }

    public function getClassAnnotations(\ReflectionClass $class)
    {
        return $this->inner->getClassAnnotations($class);
    }

    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        $result = $this->inner->getClassAnnotation($class, $annotationName);
        if (!$result && Document::class === $annotationName && $class->getName() === $this->articleViewDocumentClass) {
            $annotation = new Document();
            $annotation->type = 'article';

            return $annotation;
        }

        return $result;
    }

    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        return $this->inner->getMethodAnnotations($method);
    }

    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        return $this->inner->getMethodAnnotation($method, $annotationName);
    }

    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->inner->getPropertyAnnotations($property);
    }

    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        return $this->inner->getPropertyAnnotation($property, $annotationName);
    }
}
