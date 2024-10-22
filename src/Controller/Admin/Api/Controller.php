<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Admin\Api;

use OmniTools\Persistence\Entity;
use OmniTools\Persistence\Repository;

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
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param Repository\Api\Client $clientRepository
     * @param \Frootbox\MVC\View $view
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\InputInvalid
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        Repository\Api\Client $clientRepository,
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
        $client = $clientRepository->persist(Entity\Api\Client::fromArray([
            'title' => $post->get('title'),
            'clientId' => RandomString(),
            'clientSecret' => RandomString(),
        ]));

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
            'replace' => [
                'selector' => '#clients-receiver',
                'html' => $view->partial('OmniTools/Controller/Admin/Api/Partials/ListClients', [
                    'Highlight' => $client->getId(),
                ]),
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param Repository\Api\Client $clientRepository
     * @param \Frootbox\MVC\View $view
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        Repository\Api\Client $clientRepository,
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {
        /**
         * Fetch client
         * @var Entity\Api\Client $client
         */
        $client = $clientRepository->fetchById($get->get('ClientId'));

        // Delete client
        $client->delete();

        return new Response([
            'success' => 'Der API-Client wurde gelöscht.',
            'fadeOut' => '[data-client="' . $client->getId(). '"]',
            'replace' => [
                'selector' => '#clients-receiver',
                'html' => $view->partial('OmniTools/Controller/Admin/Api/Partials/ListClients', [

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
     * @param \Frootbox\Http\Get $get
     * @param Repository\Api\Client $clientRepository
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxModalEditAction(
        \Frootbox\Http\Get $get,
        Repository\Api\Client $clientRepository,
    ): ResponseInterface
    {
        // Fetch client
        $client = $clientRepository->fetchById($get->get('ClientId'));

        return new Response([
            'Client' => $client,
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param Repository\Api\Client $clientRepository
     * @param \Frootbox\MVC\View $view
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        Repository\Api\Client $clientRepository,
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {
        // Fetch client
        $client = $clientRepository->fetchById($get->get('ClientId'));

        $client->setTitle($post->get('Title'));
        $client->save();

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
            'replace' => [
                'selector' => '#clients-receiver',
                'html' => $view->partial('OmniTools/Controller/Admin/Api/Partials/ListClients', [
                    'Highlight' => $client->getId(),
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
