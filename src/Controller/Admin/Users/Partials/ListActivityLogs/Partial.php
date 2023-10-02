<?php
/**
 *
 */

namespace OmniTools\Controller\Admin\Users\Partials\ListActivityLogs;

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
        \Frootbox\Http\Get $get,
        \Frootbox\MVC\Translator $translator,
        \OmniTools\Persistence\Repository\Log $logRepository,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): array
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        // Fetch users company
        $company = $user->getCompany();

        $sql = 'SELECT
            l.*
        FROM
            logs l
        WHERE
            (
                l.forUserId = ' . $user->getId() . ' OR
                l.forCompanyId = ' . $company->getId() . ' 
            )
        ORDER BY
            l.date DESC';

        $logs = $logRepository->fetchByQuery($sql);

        return [
            'translator' => $translator,
            'logs' => $logs,
        ];
    }
}
