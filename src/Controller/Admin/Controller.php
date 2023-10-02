<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Admin;

use Frootbox\MVC\Response;
use Frootbox\MVC\ResponseInterface;

/**
 * @access Private
 * @userlevel Admin|Superuser
 */
class Controller extends \Frootbox\MVC\AbstractController
{
    /**
     * Generate menu
     */
    public function getMenuForAction(): ?\Frootbox\MVC\View\Menu
    {
        $user = $this->container->get(\OmniTools\Persistence\Entity\User::class);

        if (in_array($user->getAccess(), [ 'Superuser', 'Admin' ])) {

            $menu = new \Frootbox\MVC\View\Menu([
                [
                    'title' => 'Administration',
                    'items' => [
                        new \Frootbox\MVC\View\Menu\Item(
                            title: 'Benutzer',
                            url: 'Admin/Users/index',
                            icon: 'user',
                            paths: [
                                'Admin/Users/details',
                            ],
                            subItems: [
                                new \Frootbox\MVC\View\Menu\Item(
                                    title: 'Rollen',
                                    url: 'Admin/Users/Roles/index',
                                ),
                            ],
                        ),
                        new \Frootbox\MVC\View\Menu\Item(
                            title: 'API Clients',
                            url: 'Admin/Api/index',
                            icon: 'clouds',
                        ),
                    ],
                ],
                [
                    'title' => 'Wartung',
                    'items' => [
                        new \Frootbox\MVC\View\Menu\Item(
                            title: 'Import',
                            url: 'Admin/Maintenance/Import/index',
                            icon: 'file-import',
                        ),
                        new \Frootbox\MVC\View\Menu\Item(
                            title: 'Changelog',
                            url: 'Admin/changelog',
                            icon: 'clipboard-list',
                            paths: [

                            ],
                        ),
                    ],
                ],

            ]);

        }


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
     * @return ResponseInterface
     */
    public function changelogAction(

    ): ResponseInterface
    {
        // Get changelog file source
        $file = CORE_DIR . 'CHANGELOG.md';
        $source = file_exists($file) ? file_get_contents($file) : (string) null;

        if (!empty($source)) {

            // Markdown parser
            $markdownParser = new \Parsedown();

            // Parse file source
            $source = $markdownParser->text($source);
        }

        return new Response([
            'changelogHtml' => $source,
        ]);
    }

    /**
     *
     */
    public function indexAction(): ResponseInterface
    {
        return new Response;
    }
}
