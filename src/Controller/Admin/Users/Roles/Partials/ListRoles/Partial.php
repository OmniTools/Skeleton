<?php
/**
 *
 */

namespace OmniTools\Controller\Admin\Users\Roles\Partials\ListRoles;

class Partial extends \Frootbox\MVC\View\AbstractPartial
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
    
    /**
     *
     */
    public function onBeforeRendering(
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): array
    {
        // Fetch roles
        $roles = $roleRepository->fetch([
            'where' => [

            ],
            'order' => [ 'orderId DESC', 'id ASC' ],
        ]);


        return [
            'roles' => $roles,
        ];
    }
}
