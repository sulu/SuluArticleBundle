<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Projection;

use Sulu\Bundle\ArticleBundle\Controller\ArticleController;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\CreateTranslation;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ModifyTranslationStructure;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\PublishTranslation;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\RemoveArticle;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\UnpublishTranslation;
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

    public function onCreateTranslation(CreateTranslation $event): void
    {
        /** @var ArticleDocument $document */
        $document = $this->documentManager->create(ArticleController::DOCUMENT_TYPE);
        $document->setUuid($event->aggregateId());

        $this->persistDocument($document, $event->requestData(), $event->locale(), $event->createdBy());
        $this->documentManager->flush();
    }

    public function onPublishTranslation(PublishTranslation $event): void
    {
        $document = $this->documentManager->find($event->aggregateId(), $event->locale());
        $this->documentManager->publish($document, $event->locale());
        $this->documentManager->flush();
    }

    public function onUnpublishTranslation(UnpublishTranslation $event): void
    {
        $document = $this->documentManager->find($event->aggregateId(), $event->locale());
        $this->documentManager->unpublish($document, $event->locale());
        $this->documentManager->flush();
    }

    public function onModifyTranslationStructure(ModifyTranslationStructure $event): void
    {
        $document = $this->documentManager->find($event->aggregateId(), $event->locale());

        $this->persistDocument($document, $event->requestData(), $event->locale(), $event->createdBy());
        $this->documentManager->flush();
    }

    public function onRemoveArticle(RemoveArticle $event): void
    {
        $document = $this->documentManager->find($event->aggregateId());

        $this->documentManager->remove($document);
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
