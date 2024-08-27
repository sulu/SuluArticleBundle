<?php

namespace Sulu\Bundle\ArticleBundle;

use PHPCR\PhpcrMigrationsBundle\ContainerAwareInterface as PhpcrContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface as SymfonyContainerAwareInterface;

if (\interface_exists(PhpcrContainerAwareInterface::class)) {
    /**
     * @internal
     */
    interface ContainerAwareInterface extends PhpcrContainerAwareInterface
    {
    }
} elseif (\interface_exists(SymfonyContainerAwareInterface::class)) {
    /**
     * @internal
     */
    interface ContainerAwareInterface extends SymfonyContainerAwareInterface
    {
    }
} else {
    /**
     * @internal
     */
    interface ContainerAwareInterface
    {
    }
}
