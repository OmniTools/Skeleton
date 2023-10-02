<?php
/**
 *
 */

namespace Avaro\Controller\Company\Api\Partials\ListClients;

use Avaro\Persistence\Entity\Tenant;

class Partial extends \Frootbox\MVC\View\AbstractPartial
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeRendering(
        \Avaro\Persistence\Entity\Tenant $tenant,
        \Avaro\Persistence\Repository\Api\Client $clientRepository,
    ): array
    {
        // Fetch clients
        $result = $clientRepository->fetch([
            'where' => [
                'realmId' => $tenant->getId(),
            ],
        ]);

        return [
            'clients' => $result,
        ];
    }
}
