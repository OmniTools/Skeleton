<?php
/**
 *
 */

namespace OmniTools\Controller\Admin\Users\Partials\ListMails;

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
        \OmniTools\Persistence\Repository\User\Mail $mailRepository,
    ): array
    {
        // Obtain user
        $user = $this->requireParameter('user');

        // Fetch mails
        $mails = $mailRepository->fetch([
            'where' => [
                'userId' => $user->getId(),
            ],
            'order' => [  'date DESC' ],
        ]);

        return [
            'mails' => $mails,
        ];
    }
}
