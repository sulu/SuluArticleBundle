<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Command;

use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepository;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;

class UpdateArticleHandler
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

    public function __invoke(UpdateArticle $command): void
    {
        $structureType = $command->data()['template'];
        $metadata = $this->metadataFactory->getStructureMetadata('article', $structureType);

        $structureData = [];
        foreach ($metadata->getProperties() as $property) {
            if (array_key_exists($property->getName(), $command->data())) {
                $structureData[$property->getName()] = $command->data()[$property->getName()];
            }
        }

        $article = $this->repository->get($command->id());
        $article = $article->updateWithData(
            $command->locale(),
            $structureType,
            $structureData,
            $command->data(),
            $command->userId()
        );
        $this->repository->save($article);
    }
}
