<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Persistence\Repository\Api;

/**
 *
 */
class Client extends \Frootbox\Db\Model implements \Frootbox\RestApi\Interface\ClientRepositoryInterface
{
    protected string $table = 'api_clients';
    protected string $class = \OmniTools\Persistence\Entity\Api\Client::class;

    /**
     * @param \Frootbox\Db\Row $row
     * @return \Frootbox\Db\Row
     * @throws \Frootbox\Exceptions\InputInvalid
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function persist(\Frootbox\Db\Row $row): \Frootbox\Db\Row
    {
        // Start database transaction
        $this->db->transactionStart();

        // Create user
        $userRepository = $this->db->getRepository(\OmniTools\Persistence\Repository\User::class);
        $user = \OmniTools\Persistence\Entity\User::fromArray([
            'lastName' => $row->getTitle(),
            'access' => 'ApiClient',
        ]);

        // Persist user
        $userRepository->persist($user);

        // Fetch tenant
        $tenantRepository = $this->db->getRepository(\OmniTools\Persistence\Repository\Tenant::class);
        $tenant = $tenantRepository->fetchById(TENANT_ID);

        // Add user to tenant
        $tenant->userAdd($user);

        function random_str(
            int $length = 64,
            string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
        ): string {
            if ($length < 1) {
                throw new \RangeException("Length must be a positive integer");
            }
            $pieces = [];
            $max = mb_strlen($keyspace, '8bit') - 1;
            for ($i = 0; $i < $length; ++$i) {
                $pieces []= $keyspace[random_int(0, $max)];
            }
            return implode('', $pieces);
        }

        // Update row
        $row->setUserId($user->getId());
        $row->setTenantId(TENANT_ID);
        $row->setClientId(random_str(16));
        $row->setClientSecret(random_str(64));

        // Persist new row
        parent::persist($row);

        // Commit database transaction
        $this->db->transactionCommit();

        return $row;
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @return void
     */
    public function validate(string $clientId, string $clientSecret): void
    {
        $client = $this->fetchOne([
            'where' => [
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ],
        ]);

        if (empty($client)) {
            throw new \RuntimeException('Client not found');
        }
    }
}
