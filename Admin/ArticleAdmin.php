<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AutomationBundle\Admin\View\AutomationViewBuilder;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

/**
 * Integrates article-bundle into sulu-admin.
 */
class ArticleAdmin extends Admin
{
    const STRUCTURE_TAG_TYPE = 'sulu_article.type';

    const STRUCTURE_TAG_MULTIPAGE = 'sulu_article.multi_page';

    const SECURITY_CONTEXT = 'sulu.modules.articles';

    const LIST_VIEW = 'sulu_article.list';

    const ADD_FORM_VIEW = 'sulu_article.add_form';

    const EDIT_FORM_VIEW = 'sulu_article.edit_form';

    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    /**
     * @var string[]
     */
    private $kernelBundles;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManagerInterface $localizationManager,
        array $kernelBundles
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;
        $this->kernelBundles = $kernelBundles;
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
            $articleItem->setView(static::LIST_VIEW);

            $navigationItemCollection->add($articleItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureViews(ViewCollection $viewCollection): void
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
            new ToolbarAction('sulu_admin.save_with_publishing'),
            new ToolbarAction('sulu_admin.type'),
            new ToolbarAction('sulu_admin.delete'),
        ];

        $formToolbarActionsWithoutType = [
            new ToolbarAction('sulu_admin.save_with_publishing'),
        ];

        $listToolbarActions = [
            new ToolbarAction('sulu_admin.add'),
            new ToolbarAction('sulu_admin.delete'),
        ];

        $viewCollection->add(
            $this->viewBuilderFactory->createListViewBuilder(static::LIST_VIEW, '/articles/:locale')
                ->setResourceKey('articles')
                ->setListKey('articles')
                ->setTitle('sulu_article.articles')
                ->addListAdapters(['table'])
                ->addLocales($locales)
                ->setDefaultLocale($locales[0])
                ->setAddView(static::ADD_FORM_VIEW)
                ->setEditView(static::EDIT_FORM_VIEW)
                ->addToolbarActions($listToolbarActions)
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createResourceTabViewBuilder(static::ADD_FORM_VIEW, '/articles/:locale/add')
                ->setResourceKey('articles')
                ->addLocales($locales)
                ->setBackView(static::LIST_VIEW)
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createFormViewBuilder('sulu_article.add_form.details', '/details')
                ->setResourceKey('articles')
                ->setFormKey('article')
                ->setTabTitle('sulu_admin.details')
                ->setEditView(static::EDIT_FORM_VIEW)
                ->addToolbarActions($formToolbarActionsWithType)
                ->setParent(static::ADD_FORM_VIEW)
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createResourceTabViewBuilder(static::EDIT_FORM_VIEW, '/articles/:locale/:id')
                ->setResourceKey('articles')
                ->addLocales($locales)
                ->setBackView(static::LIST_VIEW)
                ->setTitleProperty('title')
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createPreviewFormViewBuilder('sulu_article.edit_form.details', '/details')
                ->setResourceKey('articles')
                ->setFormKey('article')
                ->setTabTitle('sulu_admin.details')
                ->setTabPriority(1024)
                ->addToolbarActions($formToolbarActionsWithType)
                ->setParent(static::EDIT_FORM_VIEW)
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createPreviewFormViewBuilder('sulu_article.edit_form.seo', '/seo')
                ->setResourceKey('articles')
                ->setFormKey('page_seo')
                ->setTabTitle('sulu_page.seo')
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->setParent(static::EDIT_FORM_VIEW)
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createPreviewFormViewBuilder('sulu_article.edit_form.excerpt', '/excerpt')
                ->setResourceKey('articles')
                ->setFormKey('page_excerpt')
                ->setBackView(static::LIST_VIEW)
                ->setTabTitle('sulu_page.excerpt')
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->setParent(static::EDIT_FORM_VIEW)
        );
        $viewCollection->add(
            $this->viewBuilderFactory->createPreviewFormViewBuilder('sulu_article.edit_form.settings', '/settings')
                ->setResourceKey('articles')
                ->setFormKey('article_settings')
                ->setBackView(static::LIST_VIEW)
                ->setTabTitle('sulu_page.settings')
                ->setTabPriority(512)
                ->addToolbarActions($formToolbarActionsWithoutType)
                ->setParent(static::EDIT_FORM_VIEW)
        );

        if (isset($this->kernelBundles['SuluAutomationBundle'])) {
            $viewCollection->add(
                (new AutomationViewBuilder('sulu_article.edit_form.automation', '/automation'))
                    ->setEntityClass(BasePageDocument::class)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
        }
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
            ],
        ];
    }
}
