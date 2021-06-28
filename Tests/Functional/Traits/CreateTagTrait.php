<?php

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait CreateTagTrait
{
    /**
     * @param array{
     *     name?: ?string,
     * } $data
     */
    public function createTag(array $data = []): TagInterface
    {
        $tagRepository = static::getContainer()->get('sulu.repository.tag');
        /** @var TagInterface $tag */
        $tag = $tagRepository->createNew();
        $tag->setName($data['name'] ?? '');

        static::getEntityManager()->persist($tag);

        return $tag;
    }

    protected abstract static function getEntityManager(): EntityManagerInterface;

    protected abstract static function getContainer(): ContainerInterface;
}
