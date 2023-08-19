<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Persistence\Repository;

/**
 * 
 */
class Log extends \Frootbox\Db\Model
{
    protected $table = 'logs';
    protected $class = \HandwerkConnected\Persistence\Entity\Log::class;

    /**
     * @param \Frootbox\Db\Row $row
     * @return \Frootbox\Db\Row
     */
    public function persist(\Frootbox\Db\Row $row): \Frootbox\Db\Row
    {
        if (!empty($row->getDataRaw('payload'))) {
            $row->setPayload(json_encode($row->getDataRaw('payload')));
        }

        return parent::persist($row);
    }

    /**
     * @param string $action
     * @param array|null $payload
     * @return \HandwerkConnected\Persistence\Entity\Log
     */
    public function logAction(
        string $action,
        array $payload = null,
    ): \HandwerkConnected\Persistence\Entity\Log
    {
        // Compose log
        $log = new \HandwerkConnected\Persistence\Entity\Log([
            'userId' => !empty($_SESSION['userId']) ? $_SESSION['userId'] : null,
            'action' => $action,
            'payload' => $payload,
        ]);

        return $this->persist($log);
    }

    /**
     * @param \HandwerkConnected\Persistence\Entity\Company $company
     * @param string $action
     * @param array|null $payload
     * @return \HandwerkConnected\Persistence\Entity\Log
     */
    public function logActionForCompany(
        \HandwerkConnected\Persistence\Entity\Company $company,
        string $action,
        array $payload = null,
    ): \HandwerkConnected\Persistence\Entity\Log
    {
        // Store company data
        $payload['company'] = $company->getData();

        // Compose log
        $log = new \HandwerkConnected\Persistence\Entity\Log([
            'userId' => (!empty($_SESSION['userId']) ? $_SESSION['userId'] : $company->getUserId()),
            'forUserId' => (!empty($payload['userId']) ? $payload['userId'] : null),
            'forCompanyId' => $company->getId(),
            'action' => $action,
            'payload' => $payload,
        ]);

        return $this->persist($log);
    }

    /**
     * @param \HandwerkConnected\Persistence\Entity\User $user
     * @param string $action
     * @param array|null $payload
     * @return \HandwerkConnected\Persistence\Entity\Log
     */
    public function logActionForUser(
        \OmniTools\Persistence\Entity\User $user,
        string $action,
        array $payload = null,
    ): \OmniTools\Persistence\Entity\Log
    {
        // Store company data
        $payload['user'] = $user->getData();

        // Compose log
        $log = new \OmniTools\Persistence\Entity\Log([
            'userId' => (!empty($_SESSION['userId']) ? $_SESSION['userId'] : $user->getId()),
            'forUserId' => $user->getId(),
            'action' => $action,
            'payload' => $payload,
        ]);

        return $this->persist($log);
    }
}
