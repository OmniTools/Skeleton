<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Dashboard;

use Frootbox\MVC\Response;
use Frootbox\MVC\ResponseInterface;
use Frootbox\MVC\ResponseRedirect;


/**
 *
 */
class Controller extends \Frootbox\MVC\AbstractController
{
    /**
     * Generate menu
     */
    public function getMenuForAction(): ?\Frootbox\MVC\View\Menu
    {
        $get = $this->container->get(\Frootbox\Http\Get::class);

        $menu = new \Frootbox\MVC\View\Menu([
            [
                'title' => 'Jobs',
                'items' => [
                    new \Frootbox\MVC\View\Menu\Item(
                        title: 'Übersicht',
                        url: 'Jobs/index',
                        icon: 'home',
                    ),
                    new \Frootbox\MVC\View\Menu\Item(
                        title: 'Offene Jobs',
                        url: 'Jobs/open',
                        icon: 'clipboard-list-check',
                        paths: [
                            'Jobs/Job/edit',
                        ],
                    ),
                    new \Frootbox\MVC\View\Menu\Item(
                        title: 'Neue Jobs',
                        url: 'Jobs/drafts',
                        icon: 'clipboard-list-check',
                        paths: [
                            'Jobs/Job/edit',
                        ],
                    ),
                    new \Frootbox\MVC\View\Menu\Item(
                        title: 'Archiv',
                        url: 'Jobs/archive',
                        icon: 'clipboard-list-check',
                    ),
                ],
            ],
            [
                'title' => 'Berichte',
                'items' => [
                    new \Frootbox\MVC\View\Menu\Item(
                        title: 'Neue Berichte',
                        url: 'Reports/index',
                        icon: 'clipboard-check',
                        paths: [
                            'Reports/details',
                        ],
                    ),
                    new \Frootbox\MVC\View\Menu\Item(
                        title: 'Archiv',
                        url: 'Reports/archive',
                        icon: 'clipboard-check',
                    ),
                ],
            ],
            [
                'title' => 'Datenbank',
                'items' => [
                    new \Frootbox\MVC\View\Menu\Item(
                        title: 'Benutzer',
                        url: 'Users/index',
                        icon: 'users',
                    ),
                    new \Frootbox\MVC\View\Menu\Item(
                        title: 'Märkte',
                        url: 'Markets/index',
                        icon: 'store',
                    ),
                    new \Frootbox\MVC\View\Menu\Item(
                        title: 'Kategorien',
                        url: 'Categories/index',
                        icon: 'sitemap',
                    ),
                ],
            ],
        ]);

        return $menu;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \OmniTools\Persistence\Entity\User $user
     * @return ResponseInterface
     */
    public function indexAction(
        \OmniTools\Persistence\Entity\User $user,
    ): ResponseInterface
    {
        return new Response;
    }
}
