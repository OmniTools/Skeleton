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
    protected string $table = 'users';
    protected string $class = \OmniTools\Persistence\Entity\User::class;

    /**
     * Creates a new user
     *
     * Before creation existence of email is checked and if a user with this email is found an exception is thrown.
     *
     * @param \Frootbox\Db\Row $row
     * @return \Frootbox\Db\Row
     * @throws \Frootbox\Exceptions\InputInvalid
     */
    public function persist(\Frootbox\Db\Row $row): \Frootbox\Db\Row
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

        return parent::persist($row);
    }

    /**
     * @deprecated
     *
     * @param \Frootbox\Db\Row $row
     * @return \Frootbox\Db\Row
     * @throws \Frootbox\Exceptions\InputInvalid
     */
    public function insert(
        \Frootbox\Db\Row $row,
    ): \Frootbox\Db\Row
    {
        return $this->persist($row);
    }
}
