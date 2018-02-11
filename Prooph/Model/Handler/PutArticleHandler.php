<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Handler;

use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepositoryInterface;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Command\PutArticleCommand;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;

class PutArticleHandler
{
    /**
     * @var ArticleRepositoryInterface
     */
    private $repository;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $metadataFactory;

    public function __construct(ArticleRepositoryInterface $repository, StructureMetadataFactoryInterface $metadataFactory)
    {
        $this->repository = $repository;
        $this->metadataFactory = $metadataFactory;
    }

    public function __invoke(PutArticleCommand $command): void
    {
        $structureType = $command->requestData()['template'];
        $metadata = $this->metadataFactory->getStructureMetadata('article', $structureType);

        $structureData = [];
        foreach ($metadata->getProperties() as $property) {
            if (array_key_exists($property->getName(), $command->requestData())) {
                $structureData[$property->getName()] = $command->requestData()[$property->getName()];
            }
        }

        $article = $this->repository->get($command->id());
        $article = $article->modifyTranslationStructure(
            $command->locale(),
            $structureType,
            $structureData,
            $command->userId(),
            $command->requestData()
        );
        $this->repository->save($article);
    }
}
