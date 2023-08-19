<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Persistence\Repository\User;

/**
 *
 */
class SignedAction extends \Frootbox\Db\Model
{
    protected $table = 'users_signedactions';
    protected $class = \OmniTools\Persistence\Entity\User\SignedAction::class;

    /**
     *
     */
    public function insert(\Frootbox\Db\Row $row): \Frootbox\Db\Row
    {
        // Check if signed action already exists
        $check = $this->fetchOne([
            'where' => [
                'userId' => $row->getUserId(),
                'action' => $row->getAction(),
            ],
        ]);

        // Clear existing signed action
        if (!empty($check)) {
            $check->delete();
        }

        return parent::insert($row);
    }
}
