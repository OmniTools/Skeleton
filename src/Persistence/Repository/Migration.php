<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Persistence\Repository;

/**
 * 
 */
class Migration extends \Frootbox\Db\Model
{
    protected $table = 'migrations';
    protected $class = \OmniTools\Persistence\Entity\Migration::class;
}
