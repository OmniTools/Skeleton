<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Admin\Api;

use Frootbox\Http\Get;
use Frootbox\MVC\Response;
use Frootbox\MVC\ResponseInterface;
use Frootbox\MVC\ResponseRedirect;

/**
 * @access Public
 */
class Controller extends \Frootbox\MVC\AbstractController
{
    /**
     * Generate menu
     */
    public function getMenuForAction(): ?\Frootbox\MVC\View\Menu
    {
        $calendarController = new \OmniTools\Controller\Admin\Controller;
        $calendarController->setContainer($this->container);

        return $calendarController->getMenuForAction();
    }

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
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Avaro\Persistence\Entity\Tenant $tenant,
        \Avaro\Persistence\Repository\Api\Client $clientRepository,
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {
        function RandomString(): string
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randstring = '';
            for ($i = 0; $i < 64; $i++) {
                $randstring .= $characters[rand(0, strlen($characters) - 1)];
            }
            return $randstring;
        }

        // Insert new client
        $client = $clientRepository->insert(new \Avaro\Persistence\Entity\Api\Client([
            'realmId' => $tenant->getId(),
            'title' => $post->get('title'),
            'clientKey' => RandomString(),
            'clientSecret' => RandomString(),
        ]));

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
            'replace' => [
                'selector' => '#clientsReceiver',
                'html' => $view->partial('Avaro/Controller/Company/Api/Partials/ListClients', [
                    'highlight' => $client->getId(),
                ]),
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     * @param Get $get
     * @param \Avaro\Persistence\Repository\Api\Client $clientRepository
     * @param \Frootbox\MVC\View $view
     * @return ResponseInterface
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Avaro\Persistence\Repository\Api\Client $clientRepository,
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {
        // Fetch client
        $client = $clientRepository->fetchById($get->get('clientId'));

        $client->delete();

        return new Response([
            'success' => 'Der API-Client wurde gelöscht.',
            'fadeOut' => '[data-client="' . $client->getId(). '"]',
            'replace' => [
                'selector' => '#clientsReceiver',
                'html' => $view->partial('Avaro/Controller/Company/Api/Partials/ListClients', [

                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(

    ): ResponseInterface
    {
        return new Response;
    }

    /**
     *
     */
    public function ajaxModalEditAction(
        \Frootbox\Http\Get $get,
        \Avaro\Persistence\Repository\Api\Client $clientRepository,
    ): ResponseInterface
    {
        // Fetch client
        $client = $clientRepository->fetchById($get->get('clientId'));

        return new Response([
            'client' => $client,
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Avaro\Persistence\Repository\Api\Client $clientRepository,
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {
        // Fetch client
        $client = $clientRepository->fetchById($get->get('clientId'));

        $client->setTitle($post->get('title'));
        $client->save();

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
            'replace' => [
                'selector' => '#clientsReceiver',
                'html' => $view->partial('Avaro/Controller/Company/Api/Partials/ListClients', [

                ]),
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function indexAction(

    ): ResponseInterface
    {
        return new Response();
    }
}
