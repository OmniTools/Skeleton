<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Persistence\Repository\User;

/**
 *
 */
class Role extends \Frootbox\Db\Model
{
    protected $table = 'users_roles';
    protected $class = \OmniTools\Persistence\Entity\User\Role::class;
}
