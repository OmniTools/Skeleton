<?php
/**
 *
 */

namespace OmniTools\Controller\Admin\Users\Partials\ListUsers;

use Frootbox\Config\Config;

class Partial extends \Frootbox\MVC\View\AbstractPartial
{
    protected \OmniTools\Persistence\Entity\User\Role $role;

    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param Config $configuration
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @param \OmniTools\Persistence\Repository\User\Role $roleRepository
     * @return array
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function onBeforeRendering(
        \Frootbox\Config\Config $configuration,
        \OmniTools\Persistence\Repository\User $userRepository,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): array
    {
        if ($this->hasParameter('role')) {
            $this->role = $this->requireParameter('role');
        }
        else {

            // Fetch role
            $this->role = $roleRepository->fetchById($this->requireParameter('roleId'));
        }

        if (!$this->hasParameter('result')) {

            $sql = 'SELECT
            u.*
                FROM
                    users u,
                    users_2_roles x
                WHERE
                    x.userId = u.id AND
                    x.roleId = ' . $this->role->getId() . '
            ';

            // Fetch users
            $users = $userRepository->fetchByQuery($sql);
        }
        else {
            $users = $this->requireParameter('result');
        }


        return [
            'users' => $users,
         //   'configuration' => $configuration,
        ];
    }
}
