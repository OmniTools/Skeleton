<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 */

namespace OmniTools\Persistence\Entity\User;

class Role extends \OmniTools\Persistence\Entity\AbstractRow
{
    protected $table = 'users_roles';
    protected $model = \OmniTools\Persistence\Repository\User\Role::class;
}
