<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Initializer;

use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Initializes custom-url nodes.
 */
class ArticleInitializer implements InitializerInterface
{
    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(
        NodeManager $nodeManager,
        PathBuilder $pathBuilder,
        SessionManagerInterface $sessionManager
    ) {
        $this->nodeManager = $nodeManager;
        $this->pathBuilder = $pathBuilder;
        $this->sessionManager = $sessionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(OutputInterface $output, $purge = false)
    {
        $nodeTypeManager = $this->sessionManager->getSession()->getWorkspace()->getNodeTypeManager();
        $nodeTypeManager->registerNodeType(new ArticleNodeType(), true);
        $nodeTypeManager->registerNodeType(new ArticlePageNodeType(), true);

        $articlesPath = $this->pathBuilder->build(['%base%', '%articles%']);
        if (true === $this->nodeManager->has($articlesPath)) {
            $output->writeln(sprintf('  [ ] <info>Articles path:</info>: %s ', $articlesPath));
        } else {
            $output->writeln(sprintf('  [+] <info>Articles path:</info>: %s ', $articlesPath));
            $this->nodeManager->createPath($articlesPath);
            $this->nodeManager->save();
        }
    }
}
