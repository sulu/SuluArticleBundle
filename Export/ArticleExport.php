<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Export;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Content\Structure\ExcerptStructureExtension;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Export\Export;
use Sulu\Component\Export\Manager\ExportManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

class ArticleExport extends Export implements ArticleExportInterface
{
    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    protected $extensionManager;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(
        Environment $templating,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ExportManagerInterface $exportManager,
        array $formatFilePaths
    ) {
        parent::__construct($templating, $documentManager, $documentInspector, $exportManager, $formatFilePaths);

        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
    }

    public function export(string $locale, string $format = '1.2.xliff', ?OutputInterface $output = null): string
    {
        $this->exportLocale = $locale;
        $this->format = $format;
        $this->output = $output;

        if (!$this->output) {
            $this->output = new NullOutput();
        }

        $this->output->writeln('<info>Loading Data…</info>');

        $exportData = $this->getExportData($locale);

        $this->output->writeln([
            '',
            '<info>Render Xliff…</info>',
        ]);

        return $this->templating->render(
            $this->getTemplate($this->format),
            $exportData
        );
    }

    public function getExportData(string $locale): array
    {
        /** @var ArticleDocument[] $documents */
        $documents = $this->getDocuments($locale);

        $progress = new ProgressBar($this->output, \count($documents));
        $progress->start();

        $documentData = [];
        foreach ($documents as $key => $document) {
            $contentData = $this->getContentData($document, $this->exportLocale);
            $extensionData = $this->getExtensionData($document);
            $settingData = $this->getSettingData($document);

            $documentData[] = [
                'uuid' => $document->getUuid(),
                'locale' => $document->getLocale(),
                'content' => $contentData,
                'settings' => $settingData,
                'extensions' => $extensionData,
            ];

            $progress->advance();
        }

        $progress->finish();

        return [
            'locale' => $locale,
            'format' => $this->format,
            'documents' => $documentData,
        ];
    }

    protected function getExtensionData(ArticleDocument $document): array
    {
        $data = $document->getExtensionsData();
        if ($data instanceof ExtensionContainer) {
            $data = $data->toArray();
        }

        $extensionData = [];
        foreach ($data as $extensionName => $extensionProperties) {
            /** @var ExcerptStructureExtension $extension */
            $extension = $this->extensionManager->getExtension($document->getStructureType(), $extensionName);

            if ($extension instanceof ExportExtensionInterface) {
                $extensionData[$extensionName] = $extension->export($extensionProperties, $this->format);
            }
        }

        return $extensionData;
    }

    protected function getSettingData(ArticleDocument $document): array
    {
        if ($created = $document->getCreated()) {
            $created = $created->format('c');
        }

        if ($changed = $document->getChanged()) {
            $changed = $changed->format('c');
        }

        if ($published = $document->getPublished()) {
            $published = $published->format('c');
        }

        if (($authored = $document->getAuthored()) && $authored instanceof \DateTime) {
            $authored = $authored->format('c');
        }

        $settingOptions = [];
        if ('1.2.xliff' === $this->format) {
            $settingOptions = ['translate' => false];
        }

        return [
            'structureType' => $this->createProperty('structureType', $document->getStructureType(), $settingOptions),
            'published' => $this->createProperty('published', $published, $settingOptions),
            'created' => $this->createProperty('created', $created, $settingOptions),
            'changed' => $this->createProperty('changed', $changed, $settingOptions),
            'creator' => $this->createProperty('creator', $document->getCreator(), $settingOptions),
            'changer' => $this->createProperty('changer', $document->getChanger(), $settingOptions),
            'locale' => $this->createProperty('locale', $document->getLocale(), $settingOptions),
            'shadowLocale' => $this->createProperty('shadowLocale', $document->getShadowLocale(), $settingOptions),
            'originalLocale' => $this->createProperty('originalLocale', $document->getOriginalLocale(), $settingOptions),
            'routePath' => $this->createProperty('routePath', $document->getRoutePath(), $settingOptions),
            'workflowStage' => $this->createProperty('workflowStage', $document->getWorkflowStage(), $settingOptions),
            'path' => $this->createProperty('path', $document->getPath(), $settingOptions),
            'mainWebspace' => $this->createProperty('mainWebspace', $document->getMainWebspace(), $settingOptions),
            'additionalWebspaces' => $this->createProperty('additionalWebspaces', \json_encode($document->getAdditionalWebspaces()), $settingOptions),
            'author' => $this->createProperty('author', $document->getAuthor(), $settingOptions),
            'authored' => $this->createProperty('authored', $authored, $settingOptions),
        ];
    }

    protected function getDocuments(string $locale): QueryResultCollection
    {
        $query = $this->documentManager->createQuery(
            'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "sulu:article"',
            $locale
        );

        return $query->execute();
    }

    protected function getTemplate($format)
    {
        if (!isset($this->formatFilePaths[$format])) {
            throw new ExportFormatNotFoundException(sprintf('No format "%s" configured for Snippet export', $format));
        }

        return $this->formatFilePaths[$format];
    }
}
