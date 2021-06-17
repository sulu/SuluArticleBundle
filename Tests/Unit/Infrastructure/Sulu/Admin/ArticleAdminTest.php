<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Infrastructure\Sulu\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactory;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ArticleAdminTest extends TestCase
{
    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var ObjectProphecy<ContentViewBuilderFactoryInterface>
     */
    private $contentViewBuilderFactory;

    /**
     * @var ObjectProphecy<SecurityCheckerInterface>
     */
    private $securityChecker;

    /**
     * @var ObjectProphecy<LocalizationManagerInterface>
     */
    private $localizationManager;

    /**
     * @var ArticleAdmin
     */
    private $articleAdmin;

    protected function setUp(): void
    {
        $this->viewBuilderFactory = new ViewBuilderFactory();
        $this->contentViewBuilderFactory = $this->prophesize(ContentViewBuilderFactoryInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->localizationManager = $this->prophesize(LocalizationManagerInterface::class);

        $this->articleAdmin = new ArticleAdmin(
            $this->viewBuilderFactory,
            $this->contentViewBuilderFactory->reveal(),
            $this->securityChecker->reveal(),
            $this->localizationManager->reveal()
        );
    }

    public function testConfigureViews(): void
    {
        $viewCollection = $this->prophesize(ViewCollection::class);

        $this->securityChecker->hasPermission(ArticleAdmin::SECURITY_CONTEXT, Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->localizationManager->getLocales()
            ->shouldBeCalled()
            ->willReturn(['de', 'en']);

        $contentView = $this->prophesize(ViewBuilderInterface::class);

        $this->contentViewBuilderFactory->createViews(
            ArticleInterface::class,
            ArticleAdmin::EDIT_TABS_VIEW,
            ArticleAdmin::ADD_TABS_VIEW,
            ArticleAdmin::SECURITY_CONTEXT
        )
            ->shouldBeCalled()
            ->willReturn([$contentView->reveal()]);

        $viewCollection->add(Argument::any())
            ->shouldBeCalled(4);

        $this->articleAdmin->configureViews($viewCollection->reveal());
    }

    public function testConfigureNavigationItems(): void
    {
        $navigationItemCollection = $this->prophesize(NavigationItemCollection::class);
        $navigationItemCollection->add(Argument::any())
            ->shouldBeCalledOnce();

        $this->securityChecker->hasPermission(ArticleAdmin::SECURITY_CONTEXT, PermissionTypes::EDIT)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->articleAdmin->configureNavigationItems($navigationItemCollection->reveal());
    }

    public function testConfigureNavigationItemsNoPermissions(): void
    {
        $navigationItemCollection = $this->prophesize(NavigationItemCollection::class);
        $navigationItemCollection->add(Argument::any())
            ->shouldNotBeCalled();

        $this->securityChecker->hasPermission(ArticleAdmin::SECURITY_CONTEXT, PermissionTypes::EDIT)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->articleAdmin->configureNavigationItems($navigationItemCollection->reveal());
    }

    public function testGetSecurityContexts(): void
    {
        $this->assertSame(
            [
                'Sulu' => [
                    'Global' => [
                        // using not the ArticleAdmin constant here to test regression which would need a migration
                        'sulu.article.articles' => [
                            PermissionTypes::VIEW,
                            PermissionTypes::ADD,
                            PermissionTypes::EDIT,
                            PermissionTypes::DELETE,
                            PermissionTypes::LIVE,
                        ],
                    ],
                ],
            ],
            $this->articleAdmin->getSecurityContexts()
        );
    }
}
