<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Persistence\Repository;

/**
 * 
 */
class User extends \Frootbox\Db\Model
{
    protected $table = 'users';
    protected $class = \OmniTools\Persistence\Entity\User::class;

    /**
     * @param \Frootbox\Db\Row $row
     * @return \Frootbox\Db\Row
     */
    public function insert(
        \Frootbox\Db\Row $row,
    ): \Frootbox\Db\Row
    {
        if (!empty($row->getEmail())) {
            
            // Check if username is unique
            $result = $this->fetch([
                'where' => [
                    'email' => $row->getEmail(),
                ],
                'limit' => 1
            ]);

            if ($result->getCount()) {
                throw new \Frootbox\Exceptions\InputInvalid('Ein Benutzer mit der E-Mail "' . $row->getEmail() . '" existiert bereits.');
            }
        }

        return parent::insert($row);
    }
}
