<?php
/**
 *
 */

namespace OmniTools\Controller\Admin\Api\Partials\ListClients;

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
        \OmniTools\Persistence\Repository\Api\Client $clientRepository,
    ): array
    {
        // Fetch clients
        $result = $clientRepository->fetch([
            'where' => [

            ],
        ]);

        return [
            'Clients' => $result,
        ];
    }
}
