<?php
/**
 *
 */

namespace OmniTools\Persistence\Entity\Trait;

trait User
{
    /**
     * @return \OmniTools\Persistence\Entity\User|null
     */
    public function getUser(): ?\OmniTools\Persistence\Entity\User
    {
        try {

            // Fetch user
            $userRepository = $this->getDb()->getRepository(\OmniTools\Persistence\Repository\User::class);

            return $userRepository->fetchById($this->getUserId());
        }
        catch ( \Exception $e ) {
            return null;
        }
    }

    /**
     * Obtain deleted user
     *
     * Try to fetch "deleted user". If it does not exist it will be created. The "deleted user" serves as a dummy
     * to keep for instance bookmarks and conversations functional even if the targeted user was already deleted.
     *
     * @return \HandwerkConnected\Persistence\Entity\User
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    protected function getDeletedUser(): \HandwerkConnected\Persistence\Entity\User
    {
        // Obtain deleted user
        $userRepository = $this->getDb()->getRepository(\HandwerkConnected\Persistence\Repository\User::class);
        $deletedUser = $userRepository->fetchOne([
            'where' => [
                'access' => 'DeletedUser',
            ],
        ]);

        if (empty($deletedUser)) {

            // Insert deleted user
            $deletedUser = $userRepository->insert(new \HandwerkConnected\Persistence\Entity\User([
                'access' => 'DeletedUser',
                'lastname' => 'gelöschter Benutzer',
                'email' => 'geloescht@handwerkconnected.de',
            ]));
        }

        return $deletedUser;
    }
}
