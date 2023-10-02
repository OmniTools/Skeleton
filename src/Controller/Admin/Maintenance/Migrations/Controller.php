<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Admin\Maintenance\Migrations;

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
     *
     * @return \Frootbox\MVC\View\Menu|null
     */
    public function getMenuForAction(): ?\Frootbox\MVC\View\Menu
    {
        $controller = new \OmniTools\Controller\Admin\Controller;
        $controller->setContainer($this->container);

        return $controller->getMenuForAction();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxExecuteAction(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \OmniTools\Persistence\Repository\Migration $migrationRepository,
    ): ResponseInterface
    {
        // Obtain migration
        $className = '\\OmniTools\\Migration\\Version\\V' . $get->get('version');
        $migration = $container->get($className);

        $migration->execute();

        return new Response([
            'success' => 'Die Migration wurde erfolgreich ausgeführt.',
        ]);
    }

    /**
     *
     */
    public function indexAction(
        \DI\Container $container,
        \OmniTools\Persistence\Repository\Migration $migrationRepository,
    ): ResponseInterface
    {
        // Fetch migrations
        $migrations = [];
        $directory = new \Frootbox\Filesystem\Directory(CORE_DIR . 'src/Migration/Version');

        foreach ($directory as $file) {

            if ($file == 'AbstractMigration.php') {
                continue;
            }

            $version = substr($file, 0, -4);

            $className = '\\OmniTools\\Migration\\Version\\' . substr($file, 0, -4);

            $migration = $container->get($className);

            $migrationData = [
                'migration' => $migration,
            ];

            // Check if migration was already executed
            $execution = $migrationRepository->fetchOne([
                'where' => [
                    'version' => $migration->getVersion(),
                ],
            ]);

            if ($execution) {
                $migrationData['executed'] = $execution->getDate();
                $migrationData['log'] = $execution->getLog();
            }

            $migrations[$migration->getVersion()] = $migrationData;
        }

        krsort($migrations);

        return new Response([
            'migrations' => $migrations,
        ]);
    }

}
