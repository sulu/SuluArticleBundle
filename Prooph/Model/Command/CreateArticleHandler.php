<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Command;

use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepositoryInterface;
use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleTranslation;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;

class CreateArticleHandler
{
    const ROUTE_PROPERTY = 'routePath';

    const TAG_NAME = 'sulu_article.article_route';

    /**
     * @var ArticleRepositoryInterface
     */
    private $repository;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainRouteGenerator;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    public function __construct(
        ArticleRepositoryInterface $repository,
        StructureMetadataFactoryInterface $metadataFactory,
        ChainRouteGeneratorInterface $chainRouteGenerator,
        ConflictResolverInterface $conflictResolver
    ) {
        $this->repository = $repository;
        $this->metadataFactory = $metadataFactory;
        $this->chainRouteGenerator = $chainRouteGenerator;
        $this->conflictResolver = $conflictResolver;
    }

    public function __invoke(CreateArticleCommand $command): void
    {
        $structureType = $command->requestData()['template'];
        $metadata = $this->metadataFactory->getStructureMetadata('article', $structureType);

        $structureData = [];
        foreach ($metadata->getProperties() as $property) {
            if (array_key_exists($property->getName(), $command->requestData())) {
                $structureData[$property->getName()] = $command->requestData()[$property->getName()];
            }
        }

        $propertyName = $this->findRoutePathProperty($metadata)->getName();
        $structureData[$propertyName] = $this->generateRoute($command, $structureType, $structureData, $propertyName);
        $requestData = $command->requestData();
        $requestData[$propertyName] = $structureData[$propertyName];

        $article = $this->repository->create($command->id(), $command->userId());
        $article->modifyTranslationStructure(
            $command->locale(),
            $structureType,
            $structureData,
            $command->userId(),
            $requestData
        );
        $this->repository->save($article);
    }

    private function generateRoute(
        CreateArticleCommand $command,
        string $structureType,
        array $structureData,
        string $propertyName
    ): string {
        $routePath = array_key_exists($propertyName, $structureData) ? $structureData[$propertyName] : null;

        $translation = new ArticleTranslation();
        $translation->id = $command->id();
        $translation->title = $structureData['title'];
        $translation->routePath = $routePath;
        $translation->locale = $command->locale();
        $translation->structureType = $structureType;
        $translation->structureData = $structureData;
        $translation->createdBy = $command->userId();
        $translation->createdAt = $command->createdAt();
        $translation->modifiedAt = $command->userId();
        $translation->modifiedBy = $command->createdAt();

        $route = $this->conflictResolver->resolve($this->chainRouteGenerator->generate($translation, $routePath));

        return $route->getPath();
    }

    private function findRoutePathProperty(StructureMetadata $metadata)
    {
        if ($metadata->hasTag(self::TAG_NAME)) {
            return $metadata->getPropertyByTagName(self::TAG_NAME);
        }

        return $metadata->getProperty(self::ROUTE_PROPERTY);
    }
}
