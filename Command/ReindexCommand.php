<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Command;

use PHPCR\Query\QueryResultInterface;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Reindixes articles.
 */
class ReindexCommand extends Command
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var IndexerInterface
     */
    private $draftIndexer;

    /**
     * @var IndexerInterface
     */
    private $liveIndexer;

    /**
     * @var string
     */
    private $suluContext;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        PropertyEncoder $propertyEncoder,
        DocumentManagerInterface $documentManager,
        IndexerInterface $draftIndexer,
        IndexerInterface $liveIndexer,
        string $suluContext
    ) {
        parent::__construct('sulu:article:reindex');
        $this->webspaceManager = $webspaceManager;
        $this->propertyEncoder = $propertyEncoder;
        $this->documentManager = $documentManager;
        $this->draftIndexer = $draftIndexer;
        $this->liveIndexer = $liveIndexer;
        $this->suluContext = $suluContext;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setDescription('Rebuild elastic-search index for articles');
        $this->setHelp('This command will load all articles and index them to elastic-search indexes.');
        $this->addOption('drop', null, InputOption::VALUE_NONE, 'Drop and recreate index before reindex');
        $this->addOption('clear', null, InputOption::VALUE_NONE, 'Clear all articles of index before reindex');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);

        $indexer = SuluKernel::CONTEXT_WEBSITE === $this->suluContext
            ? $this->liveIndexer
            : $this->draftIndexer;

        $output->writeln(
            sprintf('Reindex articles for the <comment>`%s`</comment> context' . PHP_EOL, $this->suluContext)
        );

        if (!$this->dropIndex($indexer, $input, $output)) {
            // Drop was canceled by user.

            return;
        }

        $indexer->createIndex();
        $this->clearIndex($indexer, $input, $output);

        $locales = $this->webspaceManager->getAllLocalizations();

        foreach ($locales as $locale) {
            $output->writeln(sprintf('<info>Locale "</info>%s<info>"</info>' . PHP_EOL, $locale->getLocale()));

            $this->indexDocuments($locale->getLocale(), $indexer, $output);

            $output->writeln(PHP_EOL);
        }

        $output->writeln(
            sprintf(
                '<info>Index rebuild completed (</info>%ss %s</info><info>)</info>',
                number_format(microtime(true) - $startTime, 2),
                $this->humanBytes(memory_get_peak_usage())
            )
        );
    }

    /**
     * Drop index if requested.
     *
     * @param IndexerInterface $indexer
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function dropIndex(IndexerInterface $indexer, InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('drop')) {
            return true;
        }

        if (!$input->getOption('no-interaction')) {
            $output->writeln(
                '<comment>ATTENTION</comment>: This operation drops and recreates the whole index and deletes the complete data.'
            );
            $output->writeln('');

            $question = new ConfirmationQuestion('Are you sure you want to drop the index? [Y/n] ');

            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');
            if (!$questionHelper->ask($input, $output, $question)) {
                return false;
            }

            $output->writeln('');
        }

        $indexer->dropIndex();

        $output->writeln(
            sprintf(
                'Dropped and recreated index for the <comment>`%s`</comment> context' . PHP_EOL,
                $this->suluContext
            )
        );

        return true;
    }

    /**
     * Clear article-content of index.
     *
     * @param IndexerInterface $indexer
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function clearIndex(IndexerInterface $indexer, InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('clear')) {
            return;
        }

        $output->writeln(sprintf('Cleared index for the <comment>`%s`</comment> context', $this->suluContext));
        $indexer->clear();
    }

    /**
     * Index documents for given locale.
     *
     * @param string $locale
     * @param IndexerInterface $indexer
     * @param OutputInterface $output
     */
    protected function indexDocuments($locale, IndexerInterface $indexer, OutputInterface $output)
    {
        $documents = $this->getDocuments($locale);
        $count = count($documents);
        if (0 === $count) {
            $output->writeln('  No documents found');

            return;
        }

        $progressBar = new ProgressBar($output, $count);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        foreach ($documents as $document) {
            $indexer->index($document);
            $progressBar->advance();
        }

        $indexer->flush();
        $progressBar->finish();
    }

    /**
     * Query for documents with given locale.
     *
     * @param string $locale
     *
     * @return QueryResultInterface
     */
    protected function getDocuments($locale)
    {
        $sql2 = sprintf(
            'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "sulu:article" AND [%s] IS NOT NULL',
            $this->propertyEncoder->localizedSystemName('template', $locale)
        );

        return $this->documentManager->createQuery($sql2, $locale, ['load_ghost_content' => false])->execute();
    }

    /**
     * Converts bytes into human readable.
     *
     * Inspired by http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
     *
     * @param int $bytes
     * @param int $dec
     *
     * @return string
     */
    protected function humanBytes($bytes, $dec = 2)
    {
        $size = ['b', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = (int) floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . $size[$factor];
    }
}
