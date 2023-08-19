<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Persistence\Repository\User;

/**
 *
 */
class RoleConnection extends \Frootbox\Db\Model
{
    protected $table = 'users_2_roles';
    protected $class = \OmniTools\Persistence\Entity\User\RoleConnection::class;
}
