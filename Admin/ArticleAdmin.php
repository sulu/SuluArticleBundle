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

use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\ActivityAdmin;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ListItemAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Bundle\AutomationBundle\Admin\AutomationAdmin;
use Sulu\Bundle\AutomationBundle\Admin\View\AutomationViewBuilderFactoryInterface;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

/**
 * Integrates article-bundle into sulu-admin.
 */
class ArticleAdmin extends Admin
{
    use StructureTagTrait;

    public const STRUCTURE_TAG_TYPE = 'sulu_article.type';

    public const STRUCTURE_TAG_MULTIPAGE = 'sulu_article.multi_page';

    public const SECURITY_CONTEXT = 'sulu.modules.articles';

    public const LIST_VIEW = 'sulu_article.list';

    public const ADD_FORM_VIEW = 'sulu_article.add_form';

    public const ADD_FORM_VIEW_DETAILS = 'sulu_article.add_form.details';

    public const EDIT_FORM_VIEW = 'sulu_article.edit_form';

    public const EDIT_FORM_VIEW_DETAILS = 'sulu_article.edit_form.details';

    public const EDIT_FORM_VIEW_SEO = 'sulu_article.edit_form.seo';

    public const EDIT_FORM_VIEW_EXCERPT = 'sulu_article.edit_form.excerpt';

    public const EDIT_FORM_VIEW_SETTINGS = 'sulu_article.edit_form.settings';

    public const EDIT_FORM_VIEW_AUTOMATION = 'sulu_article.edit_form.automation';

    public const EDIT_FORM_VIEW_ACTIVITY = 'sulu_article.edit_form.activity';

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
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var string[]
     */
    private $kernelBundles;

    /**
     * @var string[]
     */
    private $articleTypeConfigurations;

    /**
     * @var bool
     */
    private $versioningEnabled;

    /**
     * @var AutomationViewBuilderFactoryInterface|null
     */
    private $automationViewBuilderFactory;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManagerInterface $localizationManager,
        StructureManagerInterface $structureManager,
        array $kernelBundles,
        array $articleTypeConfigurations,
        bool $versioningEnabled,
        ?AutomationViewBuilderFactoryInterface $automationViewBuilderFactory = null
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;
        $this->structureManager = $structureManager;
        $this->kernelBundles = $kernelBundles;
        $this->articleTypeConfigurations = $articleTypeConfigurations;
        $this->versioningEnabled = $versioningEnabled;
        $this->automationViewBuilderFactory = $automationViewBuilderFactory;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            return;
        }

        $hasArticleTypeWithEditPermissions = false;
        foreach ($this->getTypes() as $typeKey => $typeConfig) {
            if (!$this->securityChecker->hasPermission(static::getArticleSecurityContext($typeKey), PermissionTypes::EDIT)) {
                continue;
            }

            $hasArticleTypeWithEditPermissions = true;
            break;
        }

        if (!$hasArticleTypeWithEditPermissions) {
            return;
        }

        $articleItem = new NavigationItem('sulu_article.articles');
        $articleItem->setPosition(20);
        $articleItem->setIcon('su-newspaper');
        $articleItem->setView(static::LIST_VIEW);

        $navigationItemCollection->add($articleItem);
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            return;
        }

        $locales = \array_values(
            \array_map(
                function(Localization $localization) {
                    return $localization->getLocale();
                },
                $this->localizationManager->getLocalizations()
            )
        );

        $viewCollection->add(
            $this->viewBuilderFactory->createTabViewBuilder(static::LIST_VIEW, '/articles')
                ->addRouterAttributesToBlacklist(['active', 'filter', 'limit', 'page', 'search', 'sortColumn', 'sortOrder'])
        );

        foreach ($this->getTypes() as $typeKey => $typeConfig) {
            if (!$this->securityChecker->hasPermission(static::getArticleSecurityContext($typeKey), PermissionTypes::EDIT)) {
                continue;
            }

            $formToolbarActionsWithoutType = [];
            $formToolbarActionsWithType = [];
            $listToolbarActions = [];

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD)
                && $this->securityChecker->hasPermission(static::getArticleSecurityContext($typeKey), PermissionTypes::ADD)) {
                $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
            }

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::LIVE)
                && $this->securityChecker->hasPermission(static::getArticleSecurityContext($typeKey), PermissionTypes::LIVE)) {
                $formToolbarActionsWithoutType[] = new ToolbarAction('sulu_admin.save_with_publishing');
                $formToolbarActionsWithType[] = new ToolbarAction('sulu_admin.save_with_publishing');
            } else {
                $formToolbarActionsWithoutType[] = new ToolbarAction('sulu_admin.save');
                $formToolbarActionsWithType[] = new ToolbarAction('sulu_admin.save');
            }

            $formToolbarActionsWithType[] = new ToolbarAction('sulu_admin.type');

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE)
                && $this->securityChecker->hasPermission(static::getArticleSecurityContext($typeKey), PermissionTypes::DELETE)) {
                $formToolbarActionsWithType[] = new DropdownToolbarAction(
                    'sulu_admin.delete',
                    'su-trash-alt',
                    [
                        new ToolbarAction(
                            'sulu_admin.delete',
                            [
                                'visible_condition' => '(!_permissions || _permissions.delete) && url != "/"',
                                'router_attributes_to_back_view' => ['webspace'],
                            ]
                        ),
                        new ToolbarAction(
                            'sulu_admin.delete',
                            [
                                'visible_condition' => '(!_permissions || _permissions.delete) && url != "/"',
                                'router_attributes_to_back_view' => ['webspace'],
                                'delete_locale' => true,
                            ]
                        ),
                    ]
                );

                $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
            }

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::LIVE)
                && $this->securityChecker->hasPermission(static::getArticleSecurityContext($typeKey), PermissionTypes::LIVE)) {
                $editDropdownToolbarActions = [
                    new ToolbarAction('sulu_admin.delete_draft'),
                    new ToolbarAction('sulu_admin.set_unpublished'),
                    new ToolbarAction('sulu_admin.copy'),
                ];

                if (\count($locales) > 1) {
                    $editDropdownToolbarActions[] = new ToolbarAction('sulu_admin.copy_locale');
                }

                $formToolbarActionsWithType[] = new DropdownToolbarAction(
                    'sulu_admin.edit',
                    'su-pen',
                    $editDropdownToolbarActions
                );
            }

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::VIEW)
                && $this->securityChecker->hasPermission(static::getArticleSecurityContext($typeKey), PermissionTypes::VIEW)) {
                $listToolbarActions[] = new ToolbarAction('sulu_admin.export');
            }

            $metadataRequestParameters = [
                'tags[sulu_article.type]' => false,
            ];

            if ($typeConfig['type']) {
                $metadataRequestParameters = [
                    'tags[sulu_article.type][type]' => $typeConfig['type'],
                ];
            }

            $previewCondition = '__routeAttributes.locale in availableLocales';
            $viewCollection->add(
                $this->viewBuilderFactory->createListViewBuilder(static::LIST_VIEW . '_' . $typeKey, '/:locale/' . $typeKey)
                    ->setResourceKey(ArticleDocument::RESOURCE_KEY)
                    ->setListKey(ArticleDocument::LIST_KEY)
                    ->setTabTitle($typeConfig['title'])
                    ->addListAdapters(['table'])
                    ->addLocales($locales)
                    ->addRequestParameters(['types' => $typeKey])
                    ->setDefaultLocale($locales[0])
                    ->setAddView(static::ADD_FORM_VIEW . '_' . $typeKey)
                    ->setEditView(static::EDIT_FORM_VIEW . '_' . $typeKey)
                    ->addToolbarActions($listToolbarActions)
                    ->setParent(static::LIST_VIEW)
            );

            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::ADD_FORM_VIEW . '_' . $typeKey, '/articles/:locale/' . $typeKey . '/add')
                    ->setResourceKey(ArticleDocument::RESOURCE_KEY)
                    ->addLocales($locales)
                    ->setBackView(static::LIST_VIEW . '_' . $typeKey)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder(self::ADD_FORM_VIEW_DETAILS . '_' . $typeKey, '/details')
                    ->setResourceKey(ArticleDocument::RESOURCE_KEY)
                    ->addMetadataRequestParameters($metadataRequestParameters)
                    ->setFormKey('article')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::EDIT_FORM_VIEW . '_' . $typeKey)
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->setParent(static::ADD_FORM_VIEW . '_' . $typeKey)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::EDIT_FORM_VIEW . '_' . $typeKey, '/articles/:locale/' . $typeKey . '/:id')
                    ->setResourceKey(ArticleDocument::RESOURCE_KEY)
                    ->addLocales($locales)
                    ->setTitleProperty('title')
                    ->setBackView(static::LIST_VIEW . '_' . $typeKey)
                    ->addRouterAttributesToBlacklist(['active', 'filter', 'limit', 'page', 'search', 'sortColumn', 'sortOrder'])
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createPreviewFormViewBuilder(static::EDIT_FORM_VIEW_DETAILS . '_' . $typeKey, '/details')
                    ->setResourceKey(ArticleDocument::RESOURCE_KEY)
                    ->addMetadataRequestParameters($metadataRequestParameters)
                    ->setFormKey('article')
                    ->setTabTitle('sulu_admin.details')
                    ->setTabCondition('shadowOn == false')
                    ->setTabPriority(1024)
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->setPreviewCondition($previewCondition)
                    ->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createPreviewFormViewBuilder(static::EDIT_FORM_VIEW_SEO . '_' . $typeKey, '/seo')
                    ->setResourceKey(ArticleDocument::RESOURCE_KEY)
                    ->setFormKey('page_seo')
                    ->setTabTitle('sulu_page.seo')
                    ->setTabCondition('shadowOn == false')
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->setTitleVisible(true)
                    ->setPreviewCondition($previewCondition)
                    ->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createPreviewFormViewBuilder(static::EDIT_FORM_VIEW_EXCERPT . '_' . $typeKey, '/excerpt')
                    ->setResourceKey('articles')
                    ->setFormKey('page_excerpt')
                    ->setTabTitle('sulu_page.excerpt')
                    ->setTabCondition('shadowOn == false')
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->setTitleVisible(true)
                    ->setPreviewCondition($previewCondition)
                    ->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createPreviewFormViewBuilder(static::EDIT_FORM_VIEW_SETTINGS . '_' . $typeKey, '/settings')
                    ->setResourceKey(ArticleDocument::RESOURCE_KEY)
                    ->setFormKey('article_settings')
                    ->setTabTitle('sulu_page.settings')
                    ->setTabPriority(512)
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->setTitleVisible(true)
                    ->setPreviewCondition($previewCondition)
                    ->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
            );

            if ($this->automationViewBuilderFactory
                && $this->securityChecker->hasPermission(AutomationAdmin::SECURITY_CONTEXT, PermissionTypes::EDIT)
            ) {
                $viewCollection->add(
                    $this->automationViewBuilderFactory->createTaskListViewBuilder(
                        static::EDIT_FORM_VIEW_AUTOMATION . '_' . $typeKey,
                        '/automation',
                        BasePageDocument::class
                    )->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
                );
            }

            if ($this->securityChecker->hasPermission(ActivityAdmin::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
                $viewCollection->add(
                    $this->viewBuilderFactory
                        ->createResourceTabViewBuilder(static::EDIT_FORM_VIEW_ACTIVITY . '_' . $typeKey, '/activity')
                        ->setResourceKey(ArticleDocument::RESOURCE_KEY)
                        ->setTabTitle($this->versioningEnabled ? 'sulu_admin.activity_versions' : 'sulu_admin.activity')
                        ->setTitleProperty('')
                        ->addRouterAttributesToBlacklist(['active', 'filter', 'limit', 'page', 'search', 'sortColumn', 'sortOrder'])
                        ->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
                );

                $viewCollection->add(
                    $this->viewBuilderFactory
                        ->createListViewBuilder(static::EDIT_FORM_VIEW_ACTIVITY . '_' . $typeKey . '.activity', '/activity')
                        ->setTabTitle('sulu_admin.activity')
                        ->setResourceKey(ActivityInterface::RESOURCE_KEY)
                        ->setListKey('activities')
                        ->addListAdapters(['table'])
                        ->addAdapterOptions([
                            'table' => [
                                'skin' => 'flat',
                                'show_header' => false,
                            ],
                        ])
                        ->disableTabGap()
                        ->disableSearching()
                        ->disableSelection()
                        ->disableColumnOptions()
                        ->disableFiltering()
                        ->addResourceStorePropertiesToListRequest(['id' => 'resourceId'])
                        ->addRequestParameters(['resourceKey' => ArticleDocument::RESOURCE_KEY])
                        ->setParent(static::EDIT_FORM_VIEW_ACTIVITY . '_' . $typeKey)
                );

                if ($this->versioningEnabled) {
                    $viewCollection->add(
                        $this->viewBuilderFactory
                            ->createListViewBuilder(static::EDIT_FORM_VIEW_ACTIVITY . '_' . $typeKey . '.versions', '/versions')
                            ->setTabTitle('sulu_admin.versions')
                            ->setResourceKey('article_versions')
                            ->setListKey('article_versions')
                            ->addListAdapters(['table'])
                            ->addAdapterOptions([
                                'table' => [
                                    'skin' => 'flat',
                                ],
                            ])
                            ->disableTabGap()
                            ->disableSearching()
                            ->disableSelection()
                            ->disableColumnOptions()
                            ->disableFiltering()
                            ->addRouterAttributesToListRequest(['id', 'webspace'])
                            ->addItemActions([
                                new ListItemAction('restore_version', ['success_view' => static::EDIT_FORM_VIEW . '_' . $typeKey]),
                            ])
                            ->setParent(static::EDIT_FORM_VIEW_ACTIVITY . '_' . $typeKey)
                    );
                }
            }
        }
    }

    public function getSecurityContexts()
    {
        $securityContext = [];

        foreach ($this->getTypes() as $typeKey => $type) {
            $securityContext[static::getArticleSecurityContext($typeKey)] = [
                PermissionTypes::VIEW,
                PermissionTypes::ADD,
                PermissionTypes::EDIT,
                PermissionTypes::DELETE,
                PermissionTypes::LIVE,
            ];
        }

        return [
            'Sulu' => [
                'Global' => [
                    static::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                        PermissionTypes::LIVE,
                    ],
                ],
                'Article types' => $securityContext,
            ],
        ];
    }

    private function getTypes(): array
    {
        $types = [];

        // prefill array with keys from configuration to keep order of configuration for tabs
        foreach ($this->articleTypeConfigurations as $typeKey => $articleTypeConfiguration) {
            $types[$typeKey] = [];
        }

        /** @var StructureBridge $structure */
        foreach ($this->structureManager->getStructures('article') as $structure) {
            $type = $this->getType($structure->getStructure(), null);
            $typeKey = $type ?: 'default';
            if (empty($types[$typeKey])) {
                $types[$typeKey] = [
                    'type' => $type,
                    'default' => $structure->getKey(),
                    'title' => $this->getTitle($typeKey),
                    'templates' => [],
                ];
            }

            $types[$typeKey]['templates'][$structure->getKey()] = [
                'multipage' => $this->getMultipage($structure->getStructure()),
            ];
        }

        return $types;
    }

    private function getTitle(string $type): string
    {
        if (!\array_key_exists($type, $this->articleTypeConfigurations)) {
            return \ucfirst($type);
        }

        return $this->articleTypeConfigurations[$type]['translation_key'];
    }

    /**
     * Returns security context for pages in given webspace.
     *
     * @final
     */
    public static function getArticleSecurityContext(string $typeKey): string
    {
        return \sprintf('%s_%s', static::SECURITY_CONTEXT, $typeKey);
    }
}
