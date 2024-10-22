<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 */

namespace OmniTools\Persistence\Entity\Api;

class Client extends \OmniTools\Persistence\Entity\AbstractRow
{
    protected $table = 'api_clients';
    protected $model = \OmniTools\Persistence\Repository\Api\Client::class;

    /**
     * @return \OmniTools\Persistence\Entity\User
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getUser(): \OmniTools\Persistence\Entity\User
    {
        /**
         * Fetch user
         * @var \OmniTools\Persistence\Entity\User $user
         */
        $repository = $this->getDb()->getRepository(\OmniTools\Persistence\Repository\User::class);
        $user = $repository->fetchById($this->getUserId());

        return $user;
    }
}
