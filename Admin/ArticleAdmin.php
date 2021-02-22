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
use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Bundle\AutomationBundle\Admin\View\AutomationViewBuilder;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
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

    const STRUCTURE_TAG_TYPE = 'sulu_article.type';

    const STRUCTURE_TAG_MULTIPAGE = 'sulu_article.multi_page';

    const SECURITY_CONTEXT = 'sulu.modules.articles';

    const LIST_VIEW = 'sulu_article.list';

    const ADD_FORM_VIEW = 'sulu_article.add_form';

    const ADD_FORM_VIEW_DETAILS = 'sulu_article.add_form.details';

    const EDIT_FORM_VIEW = 'sulu_article.edit_form';

    const EDIT_FORM_VIEW_DETAILS = 'sulu_article.edit_form.details';

    const EDIT_FORM_VIEW_SEO = 'sulu_article.edit_form.seo';

    const EDIT_FORM_VIEW_EXCERPT = 'sulu_article.edit_form.excerpt';

    const EDIT_FORM_VIEW_SETTINGS = 'sulu_article.edit_form.settings';

    const EDIT_FORM_VIEW_AUTOMATION = 'sulu_article.edit_form.automation';

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

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManagerInterface $localizationManager,
        StructureManagerInterface $structureManager,
        array $kernelBundles,
        array $articleTypeConfigurations
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;
        $this->structureManager = $structureManager;
        $this->kernelBundles = $kernelBundles;
        $this->articleTypeConfigurations = $articleTypeConfigurations;
    }

    /**
     * {@inheritdoc}
     */
    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            return;
        }

        $hasArticleTypeWithEditPermissions = false;
        foreach ($this->getTypes() as $typeKey => $typeConfig) {
            if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT . '_' . $typeKey, PermissionTypes::EDIT)) {
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

    /**
     * {@inheritdoc}
     */
    public function configureViews(ViewCollection $viewCollection): void
    {
        if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            return;
        }

        $locales = array_values(
            array_map(
                function(Localization $localization) {
                    return $localization->getLocale();
                },
                $this->localizationManager->getLocalizations()
            )
        );

        $viewCollection->add(
            $this->viewBuilderFactory->createTabViewBuilder(static::LIST_VIEW, '/articles')
        );

        foreach ($this->getTypes() as $typeKey => $typeConfig) {
            if (!$this->securityChecker->hasPermission(static::SECURITY_CONTEXT . '_' . $typeKey, PermissionTypes::EDIT)) {
                continue;
            }

            $formToolbarActionsWithoutType = [];
            $formToolbarActionsWithType = [];
            $listToolbarActions = [];

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD)
                && $this->securityChecker->hasPermission(static::SECURITY_CONTEXT . '_' . $typeKey, PermissionTypes::ADD)) {
                $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
            }

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::LIVE)
                && $this->securityChecker->hasPermission(static::SECURITY_CONTEXT . '_' . $typeKey, PermissionTypes::LIVE)) {
                $formToolbarActionsWithoutType[] = new ToolbarAction('sulu_admin.save_with_publishing');
                $formToolbarActionsWithType[] = new ToolbarAction('sulu_admin.save_with_publishing');
            } else {
                $formToolbarActionsWithoutType[] = new ToolbarAction('sulu_admin.save');
                $formToolbarActionsWithType[] = new ToolbarAction('sulu_admin.save');
            }

            $formToolbarActionsWithType[] = new ToolbarAction('sulu_admin.type');

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE)
                && $this->securityChecker->hasPermission(static::SECURITY_CONTEXT . '_' . $typeKey, PermissionTypes::DELETE)) {
                // TODO remove when ArticleBundle requires Sulu ^2.3
                if ((new \ReflectionClass(DocumentManagerInterface::class))->hasMethod('removeLocale')) {
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
                } else {
                    $formToolbarActionsWithType[] = new ToolbarAction('sulu_admin.delete');
                }

                $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
            }

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::LIVE)
                && $this->securityChecker->hasPermission(static::SECURITY_CONTEXT . '_' . $typeKey, PermissionTypes::LIVE)) {
                $formToolbarActionsWithType[] = new DropdownToolbarAction(
                    'sulu_admin.edit',
                    'su-pen',
                    [
                        new ToolbarAction('sulu_admin.delete_draft'),
                        new ToolbarAction('sulu_admin.set_unpublished'),
                    ]
                );
            }

            if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::VIEW)
                && $this->securityChecker->hasPermission(static::SECURITY_CONTEXT . '_' . $typeKey, PermissionTypes::VIEW)) {
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

            $viewCollection->add(
                $this->viewBuilderFactory->createListViewBuilder(static::LIST_VIEW . '_' . $typeKey, '/:locale/' . $typeKey)
                    ->setResourceKey('articles')
                    ->setListKey('articles')
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
                    ->setResourceKey('articles')
                    ->addLocales($locales)
                    ->setBackView(static::LIST_VIEW . '_' . $typeKey)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder(self::ADD_FORM_VIEW_DETAILS . '_' . $typeKey, '/details')
                    ->setResourceKey('articles')
                    ->addMetadataRequestParameters($metadataRequestParameters)
                    ->setFormKey('article')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::EDIT_FORM_VIEW . '_' . $typeKey)
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->setParent(static::ADD_FORM_VIEW . '_' . $typeKey)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::EDIT_FORM_VIEW . '_' . $typeKey, '/articles/:locale/' . $typeKey . '/:id')
                    ->setResourceKey('articles')
                    ->addLocales($locales)
                    ->setBackView(static::LIST_VIEW . '_' . $typeKey)
                    ->setTitleProperty('title')
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createPreviewFormViewBuilder(static::EDIT_FORM_VIEW_DETAILS . '_' . $typeKey, '/details')
                    ->setResourceKey('articles')
                    ->addMetadataRequestParameters($metadataRequestParameters)
                    ->setFormKey('article')
                    ->setTabTitle('sulu_admin.details')
                    ->setTabPriority(1024)
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createPreviewFormViewBuilder(static::EDIT_FORM_VIEW_SEO . '_' . $typeKey, '/seo')
                    ->setResourceKey('articles')
                    ->setFormKey('page_seo')
                    ->setTabTitle('sulu_page.seo')
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createPreviewFormViewBuilder(static::EDIT_FORM_VIEW_EXCERPT . '_' . $typeKey, '/excerpt')
                    ->setResourceKey('articles')
                    ->setFormKey('page_excerpt')
                    ->setTabTitle('sulu_page.excerpt')
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createPreviewFormViewBuilder(static::EDIT_FORM_VIEW_SETTINGS . '_' . $typeKey, '/settings')
                    ->setResourceKey('articles')
                    ->setFormKey('article_settings')
                    ->setTabTitle('sulu_page.settings')
                    ->setTabPriority(512)
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
            );

            if (isset($this->kernelBundles['SuluAutomationBundle'])) {
                $viewCollection->add(
                    (new AutomationViewBuilder(static::EDIT_FORM_VIEW_AUTOMATION . '_' . $typeKey, '/automation'))
                        ->setEntityClass(BasePageDocument::class)
                        ->setParent(static::EDIT_FORM_VIEW . '_' . $typeKey)
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        $securityContext = [];

        foreach ($this->getTypes() as $typeKey => $type) {
            $securityContext[static::SECURITY_CONTEXT . '_' . $typeKey] = [
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
        if (!array_key_exists($type, $this->articleTypeConfigurations)) {
            return ucfirst($type);
        }

        return $this->articleTypeConfigurations[$type]['translation_key'];
    }
}
