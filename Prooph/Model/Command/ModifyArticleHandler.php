<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Command;

use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepository;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;

class ModifyArticleHandler
{
    /**
     * @var ArticleRepository
     */
    private $repository;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $metadataFactory;

    public function __construct(ArticleRepository $repository, StructureMetadataFactoryInterface $metadataFactory)
    {
        $this->repository = $repository;
        $this->metadataFactory = $metadataFactory;
    }

    public function __invoke(ModifyArticleCommand $command): void
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
