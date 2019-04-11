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

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;

/**
 * Integrates article-bundle into sulu-admin.
 */
class ArticleAdmin extends Admin
{
    use StructureTagTrait;

    const STRUCTURE_TAG_TYPE = 'sulu_article.type';

    const STRUCTURE_TAG_MULTIPAGE = 'sulu_article.multi_page';

    const SECURITY_CONTEXT = 'sulu.modules.articles';

    private $securityContext;

    /**
     * @param SecurityCheckerInterface $securityChecker
     * @param StructureManagerInterface $structureManager
     * @param string $title
     * @param array $securityContext
     */
    public function __construct
    (
        SecurityCheckerInterface $securityChecker,
        StructureManagerInterface $structureManager,
        $title,
        array $securityContext
    )
    {
        $rootNavigationItem = new NavigationItem($title);
        $section = new NavigationItem('navigation.modules');
        $section->setPosition(20);
        $types = [];
        $this->securityContext = $securityContext;

        foreach ($structureManager->getStructures('article') as $key => $structure) {
            $type = $this->getType($structure->getStructure());
            if (!array_key_exists($type, $types)) {
                $types[$type] = [
                    'type' => $structure->getKey(),
                ];
            }
            $this->securityContext[self::SECURITY_CONTEXT . ' ' . $type] = [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                        PermissionTypes::LIVE,
                    ];
        }

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
        $contexts = [
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
                    'Article type access' => $this->securityContext,
                ]
            ];

        return $contexts;
    }
}
