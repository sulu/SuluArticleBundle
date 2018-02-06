<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Projection;

use Sulu\Bundle\ArticleBundle\Controller\ArticleController;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ArticleCreated;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ArticlePublished;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ArticleUnpublished;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ArticleUpdated;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\Form\FormFactory;

class ArticleDocumentProjector
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var FormFactory
     */
    private $formFactory;

    public function __construct(DocumentManagerInterface $documentManager, MetadataFactoryInterface $metadataFactory, FormFactory $formFactory)
    {
        $this->documentManager = $documentManager;
        $this->metadataFactory = $metadataFactory;
        $this->formFactory = $formFactory;
    }

    public function onArticleCreated(ArticleCreated $event): void
    {
        /** @var ArticleDocument $document */
        $document = $this->documentManager->create(ArticleController::DOCUMENT_TYPE);
        $document->setUuid($event->aggregateId());

        $this->persistDocument($document, $event->data(), $event->locale(), $event->creator());
        $this->documentManager->flush();
    }

    public function onArticlePublished(ArticlePublished $event): void
    {
        $document = $this->documentManager->find($event->aggregateId(), $event->locale());
        $this->documentManager->publish($document, $event->locale());
        $this->documentManager->flush();
    }

    public function onArticleUnpublished(ArticleUnpublished $event): void
    {
        $document = $this->documentManager->find($event->aggregateId(), $event->locale());
        $this->documentManager->unpublish($document, $event->locale());
        $this->documentManager->flush();
    }

    public function onArticleUpdated(ArticleUpdated $event): void
    {
        $document = $this->documentManager->find($event->aggregateId(), $event->locale());

        $this->persistDocument($document, $event->data(), $event->locale(), $event->changer());
        $this->documentManager->flush();
    }

    private function persistDocument(ArticleDocument $document, array $data, string $locale, int $userId)
    {
        $formType = $this->metadataFactory->getMetadataForAlias('article')->getFormType();
        $form = $this->createForm(
            $formType,
            $document,
            [
                // disable csrf protection, since we can't produce a token, because the form is cached on the client
                'csrf_protection' => false,
            ]
        );
        $form->submit($data, false);

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        if (array_key_exists('author', $data) && null === $data['author']) {
            $document->setAuthor(null);
        }

        $this->documentManager->persist(
            $document,
            $locale,
            [
                'user' => $userId,
                'clear_missing_content' => false,
            ]
        );
    }

    protected function createForm($type, $data = null, array $options = array())
    {
        return $this->formFactory->create($type, $data, $options);
    }
}
