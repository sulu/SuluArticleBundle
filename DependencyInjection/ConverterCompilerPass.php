<?php

namespace Sulu\Bundle\ArticleBundle\DependencyInjection;

use Sulu\Bundle\ArticleBundle\Elasticsearch\EventAwareConverter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Replaces class and add argument for replaced service "es.result_converter".
 */
class ConverterCompilerPass implements CompilerPassInterface
{
    const SERVICE_ID = 'es.result_converter';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::SERVICE_ID);
        $definition->setClass(EventAwareConverter::class);
        $definition->addArgument(new Reference('event_dispatcher'));
    }
}
