<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 */

namespace OmniTools\Persistence\Entity;

class Migration extends \OmniTools\Persistence\Entity\AbstractRow
{
    protected $table = 'migrations';
    protected $model = \OmniTools\Persistence\Repository\Migration::class;
}
