<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Command;

use Sulu\Bundle\ArticleBundle\Prooph\Model\Article;
use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleRepository;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;

class CreateArticleHandler
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

    public function __invoke(CreateArticle $command): void
    {
        // TODO generate route-path (add it to structure-data and data)

        $structureType = $command->data()['template'];
        $metadata = $this->metadataFactory->getStructureMetadata('article', $structureType);

        $structureData = [];
        foreach ($metadata->getProperties() as $property) {
            if (array_key_exists($property->getName(), $command->data())) {
                $structureData[$property->getName()] = $command->data()[$property->getName()];
            }
        }

        $article = Article::createWithData(
            $command->id(),
            $command->locale(),
            $structureType,
            $structureData,
            $command->data(),
            $command->userId()
        );
        $this->repository->save($article);
    }
}
