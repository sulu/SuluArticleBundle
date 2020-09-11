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

use Symfony\Component\Console\Output\OutputInterface;

interface ArticleExportInterface
{
    public function export(string $locale, string $format = '1.2.xliff', ?OutputInterface $output = null): string;
}
