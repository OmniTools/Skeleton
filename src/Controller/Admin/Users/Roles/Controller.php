<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Admin\Users\Roles;

use Frootbox\MVC\ResponseInterface;
use Frootbox\MVC\Response;

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
        $calendarController = new \OmniTools\Controller\Admin\Controller;
        $calendarController->setContainer($this->container);

        return $calendarController->getMenuForAction();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function ajaxAssignToAllAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User $userRepository,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): ResponseInterface
    {
        // Fetch role
        $role = $roleRepository->fetchById($get->get('roleId'));

        // Fetch users
        $users = $userRepository->fetch();

        foreach ($users as $user) {

            $user->roleAdd($role);
        }

        return new Response([

        ]);
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \OmniTools\Persistence\Repository\User\Role $roleRepository
     * @param \Frootbox\MVC\View $view
     * @return ResponseInterface
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {
        // Compose new role
        $role = new \OmniTools\Persistence\Entity\User\Role([
            'title' => $post->get('Title'),
            'roleKey' => ucfirst($post->get('RoleKey')),
        ]);

        // Persist new role
        $role = $roleRepository->persist($role);

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#roles-receiver',
                'html' => $view->partial('OmniTools/Controller/Admin/Users/Roles/Partials/ListRoles', [
                    'highlight' => $role->getId(),
                ]),
            ],
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @param \OmniTools\Persistence\Repository\User\Role $roleRepository
     * @param \Frootbox\MVC\View $view
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User $userRepository,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {
        // Fetch role
        $role = $roleRepository->fetchById($get->get('roleId'));

        // Delete role
        $role->delete();

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#roles-receiver',
                'html' => $view->partial('OmniTools/Controller/Admin/Users/Roles/Partials/ListRoles'),
            ],
        ]);
    }


    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): ResponseInterface
    {
        $orderId = count($get->get('roles')) + 1;

        foreach ($get->get('roles') as $roleId) {

            // Fetch role
            $role = $roleRepository->fetchById($roleId);

            $role->setOrderId($orderId--);
            $role->save();
        }

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \OmniTools\Persistence\Repository\User\Role $roleRepository
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): ResponseInterface
    {
        // Fetch role
        $role = $roleRepository->fetchById($get->get('roleId'));

        // Update role
        $role->setTitle($post->get('Title'));
        $role->setRoleKey($post->get('RoleKey'));
        $role->save();

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
        ]);
    }

    public function ajaxModalComposeAction(

    ): ResponseInterface
    {
        return new Response([

        ]);
    }

    public function ajaxModalDeleteAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): ResponseInterface
    {
        // Fetch role
        $role = $roleRepository->fetchById($get->get('roleId'));

        // Fetch roles
        $roles = $roleRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\NotEqual('id', $role->getId()),
            ],
        ]);

        return new Response([
            'role' => $role,
            'roles' => $roles,
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \OmniTools\Persistence\Repository\User\Role $roleRepository
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): ResponseInterface
    {
        // Fetch role
        $role = $roleRepository->fetchById($get->get('roleId'));

        return new Response([
            'role' => $role,
        ]);
    }


    public function indexAction(

    ): ResponseInterface
    {
        return new Response();

    }
}
