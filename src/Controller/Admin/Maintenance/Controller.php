<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Admin\Maintenance;

use DI\Container;
use Frootbox\MVC\Response;
use Frootbox\MVC\ResponseInterface;

/**
 * @access Private
 * @userlevel Admin|Superuser
 */
class Controller extends \Frootbox\MVC\AbstractController
{
    /**
     * Generate menu
     */
    public function getMenuForAction(): ?\HandwerkConnected\View\Menu
    {
        $calendarController = new \HandwerkConnected\Controller\Admin\Controller;
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
    public function indexAction(

    ): ResponseInterface
    {


        return new Response([

        ]);
    }

}
