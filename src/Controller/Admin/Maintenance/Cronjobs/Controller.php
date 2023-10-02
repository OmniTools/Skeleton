<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Admin\Maintenance\Cronjobs;

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
    public function ajaxPlayAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
    ): ResponseInterface
    {
        $executable = $config->get('php.executable') . ' ' . CORE_DIR . 'bin/cron-' . basename($get->get('cron')) . '.php';

        exec($executable, $output, $resultCode);

        return new Response([
            'success' => 'Der Cronjob wurde erfolgreich ausgeführt.',
            'setClasses' => [
                'remove' => 'd-none',
                'selector' => '.console.' . $get->get('cron'),
            ],
            'replace' => [
                'selector' => '.console.' . $get->get('cron'),
                'html' => implode("<br />", $output),
            ],
        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Config\Config $config,
    ): ResponseInterface
    {
        return new Response([
            'phpPath' => $config->get('php.executable'),
            'binPath' => CORE_DIR . 'bin/',
        ]);
    }

}
