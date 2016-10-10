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
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

/**
 * Integrates article-bundle into sulu-admin.
 */
class ArticleAdmin extends Admin
{
    const STRUCTURE_TAG_TYPE = 'sulu_article.type';
    const SECURITY_CONTEXT = 'sulu.modules.articles';

    /**
     * @param SecurityCheckerInterface $securityChecker
     * @param string $title
     */
    public function __construct(SecurityCheckerInterface $securityChecker, $title)
    {
        $rootNavigationItem = new NavigationItem($title);
        $section = new NavigationItem('navigation.modules');
        $section->setPosition(20);

        if ($securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $roles = new NavigationItem('sulu_article.title', $section);
            $roles->setPosition(9);
            $roles->setAction('articles');
            $roles->setIcon('newspaper-o');
        }

        if ($section->hasChildren()) {
            $rootNavigationItem->addChild($section);
        }

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'suluarticle';
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
