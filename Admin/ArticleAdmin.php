<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteCollection;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;

/**
 * Integrates article-bundle into sulu-admin.
 */
class ArticleAdmin extends Admin
{
    const STRUCTURE_TAG_TYPE = 'sulu_article.type';

    const STRUCTURE_TAG_MULTIPAGE = 'sulu_article.multi_page';

    const SECURITY_CONTEXT = 'sulu.modules.articles';

    const LIST_ROUTE = 'sulu_article.list';

    const ADD_FORM_ROUTE = 'sulu_article.add_form';

    const EDIT_FORM_ROUTE = 'sulu_article.edit_form';

    /**
     * @var RouteBuilderFactoryInterface
     */
    private $routeBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    public function __construct(
        RouteBuilderFactoryInterface $routeBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManagerInterface $localizationManager
    ) {
        $this->routeBuilderFactory = $routeBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, 'view')) {
            $articleItem = new NavigationItem('sulu_article.articles');
            $articleItem->setPosition(20);
            $articleItem->setIcon('su-newspaper');
            $articleItem->setMainRoute(static::LIST_ROUTE);

            $navigationItemCollection->add($articleItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureRoutes(RouteCollection $routeCollection): void
    {
        $locales = array_values(
            array_map(
                function(Localization $localization) {
                    return $localization->getLocale();
                },
                $this->localizationManager->getLocalizations()
            )
        );

        $formToolbarActionsWithType = [
            'sulu_admin.save_with_publishing',
            'sulu_admin.type',
            'sulu_admin.delete',
        ];

        $formToolbarActionsWithoutType = [
            'sulu_admin.save_with_publishing',
        ];

        $listToolbarActions = [
            'sulu_admin.add',
            'sulu_admin.delete',
        ];

        $routeCollection->add(
            $this->routeBuilderFactory->createListRouteBuilder(static::LIST_ROUTE, '/articles/:locale')
                ->setResourceKey('articles')
                ->setListKey('articles')
                ->setTitle('sulu_article.articles')
                ->addListAdapters(['table'])
                ->addLocales($locales)
                ->setDefaultLocale($locales[0])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($listToolbarActions)
        );
        $routeCollection->add(
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::ADD_FORM_ROUTE, '/articles/:locale/add')
                ->setResourceKey('articles')
                ->addLocales($locales)
                ->setBackRoute(static::LIST_ROUTE)
        );
        $routeCollection->add(
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_article.add_form.details', '/details')
                ->setResourceKey('articles')
                ->setFormKey('article')
                ->setTabTitle('sulu_admin.details')
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($formToolbarActionsWithType)
                ->setParent(static::ADD_FORM_ROUTE)
        );
        $routeCollection->add(
            $this->routeBuilderFactory->createResourceTabRouteBuilder(static::EDIT_FORM_ROUTE, '/articles/:locale/:id')
                ->setResourceKey('articles')
                ->addLocales($locales)
                ->setBackRoute(static::LIST_ROUTE)
                ->setTitleProperty('title')
        );
        $routeCollection->add(
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_article.edit_form.details', '/details')
                ->setResourceKey('articles')
                ->setFormKey('article')
                ->setTabTitle('sulu_admin.details')
                ->setTabPriority(1024)
                ->addToolbarActions($formToolbarActionsWithType)
                ->setParent(static::EDIT_FORM_ROUTE)
        );
        $routeCollection->add(
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_article.edit_form.seo', '/seo')
                ->setResourceKey('articles')
                ->setFormKey('page_seo')
                ->setTabTitle('sulu_page.seo')
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->setParent(static::EDIT_FORM_ROUTE)
        );
        $routeCollection->add(
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_article.edit_form.excerpt', '/excerpt')
                ->setResourceKey('articles')
                ->setFormKey('page_excerpt')
                ->setBackRoute(static::LIST_ROUTE)
                ->setTabTitle('sulu_page.excerpt')
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->setParent(static::EDIT_FORM_ROUTE)
        );
        $routeCollection->add(
            $this->routeBuilderFactory->createFormRouteBuilder('sulu_article.edit_form.settings', '/settings')
                ->setResourceKey('articles')
                ->setFormKey('article_settings')
                ->setBackRoute(static::LIST_ROUTE)
                ->setTabTitle('sulu_page.settings')
                ->setTabPriority(512)
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->setParent(static::EDIT_FORM_ROUTE)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Global' => [
                    self::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                        PermissionTypes::LIVE,
                    ],
                ],
            ]
        ];
    }
}
