<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Admin\Maintenance\Import;

use DI\Container;
use Frootbox\MVC\Response;
use Frootbox\MVC\ResponseInterface;

use function Sentry\continueTrace;

/**
 * @access Private
 * @userlevel Admin|Superuser
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
     *
     */
    public function ajaxDatabaseResetAction(
        \Frootbox\Db\Db $db,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        if ($post->get('confirmation') != 'K5JF273') {
            throw new \Frootbox\Exceptions\InputInvalid('Der Sicherheitscode ist nicht korrekt.');
        }

        // Clear users
        $users = $userRepository->fetch([
            'where' => [
                'access' => 'User',
            ],
        ]);

        $users->map('delete');

        $check = $userRepository->fetchOne([
            'order' => [ 'id DESC' ],
        ]);

        $sql = 'ALTER TABLE users AUTO_INCREMENT = ' . ($check->getId() + 1) . ';';
        $stmt = $db->query($sql);
        $stmt->execute();

        // Fetch tables
        $sql = 'SHOW TABLES';
        $stmt = $db->query($sql);
        $stmt->execute();

        foreach ($stmt->fetchAll() as $table) {
            $table = array_values($table)[0];

            // Skip users table, it has been cleaned up already
            if (in_array($table, [ 'users', 'configurations', 'migrations' ])) {
                continue;
            }

            $sql = 'TRUNCATE TABLE ' . $table;
            $db->query($sql);
        }

        return new Response([
            'success' => 'Die Datenbank wurde zurückgesetzt.',
            'modalDismiss' => true,
        ]);
    }

    public function ajaxBaseImportAction(

    ): ResponseInterface
    {
        $file = CORE_DIR . 'Export.csv';

        // Read a CSV file
        $handle = fopen($file, "r");

        $lineNumber = 0;

        // Iterate over every line of the file
        while (($raw_string = fgets($handle)) !== false) {

            ++$lineNumber;

            if ($lineNumber == 1) {
                continue;
            }

            $row = str_getcsv($raw_string, ';');

            $values = str_repeat('?,', count($data[0]) - 1) . '?';
// construct the entire query
            $sql = "INSERT INTO table (columnA, columnB) VALUES " .
                // repeat the (?,?) sequence for each row
                str_repeat("($values),", count($data) - 1) . "($values)";

            $stmt = $pdo->prepare ($sql);
// execute with all values from $data
            $stmt->execute(array_merge(...$data));

            d($row);
        }
    }

    /**
     *
     */
    public function ajaxImportMarketsAction(
        \OmniTools\Persistence\Repository\User $userRepository,
        \OmniTools\Persistence\Repository\Market $marketRepository,
    ): ResponseInterface
    {
        $file = CORE_DIR . 'Export.csv';

        // Read a CSV file
        $handle = fopen($file, "r");

        // Optionally, you can keep the number of the line where
        // the loop its currently iterating over
        $lineNumber = 0;
        $loop = 0;

        // Iterate over every line of the file
        while (($raw_string = fgets($handle)) !== false) {

            ++$lineNumber;

            // Parse the raw csv string: "1, a, b, c"
            $row = str_getcsv($raw_string, ';');

            if (count($row) == 1) {
                continue;
            }

            if ($lineNumber == 1) {
                continue;
            }

            // Check market
            $market = $marketRepository->fetchOne([
                'where' => [
                    'hpId' => $row[0],
                ],
            ]);

            if (empty($market)) {

                // Compose market
                $market = new \OmniTools\Persistence\Entity\Market([
                    'hpId' => $row[0],
                    'title' => $row[1],

                    'street' => $row[2],
                    'postalCode' => $row[3],
                    'city' => $row[4],
                ]);

                if (!empty($row[5])) {
                    $market->setLng(str_replace(',', '.', $row[5]));
                }

                if (!empty($row[65])) {
                    $market->setLat(str_replace(',', '.', $row[5]));
                }


                try {
                    // Create market
                    $market = $marketRepository->insert($market);

                    ++$loop;
                }
                catch (\Exception $e) {
                    p($e->getMessage());
                    p($row);
                    exit;
                }

            }
        }


        fclose($handle);

        return new Response([
            'success' => 'Es wurden ' . $loop . ' Märkte importiert.',
            'setClasses' => [
                'add' => 'text-success',
                'selector' => 'tr.ajaxImportMarkets',
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxImportMarketsCategoriesAction(
        \OmniTools\Persistence\Repository\Market $marketRepository,
        \OmniTools\Persistence\Repository\Market\Category $categoryRepository,
    ): ResponseInterface
    {
        $file = CORE_DIR . 'Export.csv';

        // Read a CSV file
        $handle = fopen($file, "r");

        // Optionally, you can keep the number of the line where
        // the loop its currently iterating over
        $lineNumber = 0;
        $loop = 0;

        // Get root node
        $root = $categoryRepository->fetchOne([
            'where' => [
                new \Frootbox\Db\Conditions\MatchColumn('id', 'rootId'),
            ],
        ]);

        if (empty($root)) {

            $root = new \OmniTools\Persistence\Entity\Market\Category([
                'title' => 'Index',
            ]);

            $root = $categoryRepository->insertRoot($root);
        }

        /**
         * @param string $string
         * @return array
         */
        function getSections(string $string): array
        {
            $sections = explode('\\', $string);
            $list = [];
            $last = null;

            foreach ($sections as $section) {

                $section = trim($section);
                $check = strtolower($section);

                if ($last == $check) {
                    continue;
                }

                $list[] = $section;

                $last = $check;
            }

            array_map('trim', $list);

            return $list;
        }

        $check = [];

        // Iterate over every line of the file
        while (($raw_string = fgets($handle)) !== false) {
            ++$lineNumber;

            // Parse the raw csv string: "1, a, b, c"
            $row = str_getcsv($raw_string, ';');

            if (count($row) == 1) {
                continue;
            }

            if ($lineNumber == 1) {
                continue;
            }

            // Check market
            $market = $marketRepository->fetchOne([
                'where' => [
                    'hpId' => $row[0],
                ],
            ]);

            if (empty($market)) {
                d($row);
            }

            $string = $row[7];
            $key = md5($string);

            if (in_array($key, $check)) {
                continue;
            }

            $sections = getSections($string);

            $root->reload();
            $node = $root;

            $checksumArray = [];

            foreach ($sections as $section) {

                $checksumArray[] = $section;

                // Check node
                $category = $categoryRepository->fetchOne([
                    'where' => [
                        'parentId' => $node->getId(),
                        'title' => $section,
                    ]
                ]);

                if (empty($category)) {

                    // Compose new category
                    $category = new \OmniTools\Persistence\Entity\Market\Category([
                        'title' => $section,
                        'checksum' => md5(json_encode($checksumArray)),
                    ]);

                    // Persist category
                    $category = $node->appendChild($category);
                    $node->reload();

                    ++$loop;
                }

                $node = $category;
            }

            $check[] = $key;
        }

        return new Response([
            'success' => 'Es wurden ' . $loop . ' Kategorien importiert.',
            'setClasses' => [
                'add' => 'text-success',
                'selector' => 'tr.ajaxImportMarketsCategories',
            ],
        ]);
    }

    public function ajaxImportMarketCategoryConnectionsAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\Market $marketRepository,
        \OmniTools\Persistence\Repository\Market\Category $categoryRepository,
        \OmniTools\Persistence\Repository\Market\CategoryConnection $categoryConnectionRepository,
    ): ResponseInterface
    {
        $file = CORE_DIR . 'Export.csv';

        // Read a CSV file
        $handle = fopen($file, "r");

        // Optionally, you can keep the number of the line where
        // the loop its currently iterating over
        $lineNumber = 0;
        $start = ((int) $get->get('lineCurrent')) - 1;
        $break = $start + (int) $get->get('break');

        if ($start < 0) {
            $start = 0;
        }

        $loop = 0;

        $imported = [];

        /**
         * @param string $string
         * @return array
         */
        function getSections(string $string): array
        {
            $sections = explode('\\', $string);
            $list = [];
            $last = null;

            foreach ($sections as $section) {

                $section = trim($section);
                $check = strtolower($section);

                if ($last == $check) {
                    continue;
                }

                $list[] = $section;

                $last = $check;
            }

            array_map('trim', $list);

            return $list;
        }

        // Iterate over every line of the file
        while (($raw_string = fgets($handle)) !== false) {
            ++$lineNumber;

            if ($lineNumber < $start) {
                continue;
            }

            if ($lineNumber >= $break) {
                break;
            }

            // Parse the raw csv string: "1, a, b, c"
            $row = str_getcsv($raw_string, ';');

            if ($lineNumber == 1) {
                continue;
            }

            $string = $row[7];

            $sections = getSections($string);

            $checksum = md5(json_encode($sections));

            $market = $marketRepository->fetchOne([
                'where' => [
                    'hpId' => $row[0],
                ],
            ]);

            if (empty($market)) {
                continue;
            }

            $category = $categoryRepository->fetchOne([
                'where' => [
                    'checksum' => $checksum,
                ],
            ]);

            if (empty($category)) {
                continue;
                p("NO CAT");
                p($row);
                p($market);
                exit;
            }

            // CHeck category
            $check = $categoryConnectionRepository->fetchOne([
                'where' => [
                    'marketId' => $market->getId(),
                    'categoryId' => $category->getId(),
                ],
            ]);

            if (!empty($check)) {
                continue;
            }

            // Compose conenction
            $categoryConnection = new \OmniTools\Persistence\Entity\Market\CategoryConnection([
                'marketId' => $market->getId(),
                'categoryId' => $category->getId(),
            ]);

            // Persist connection
            $categoryConnectionRepository->persist($categoryConnection);

            ++$loop;
        }

        return new Response([
            'success' => 'Es wurden ' . $loop . ' Märkte mit Kategorien verknüpft.',
            'currentLine' => $lineNumber,
            'setClasses' => [
                'add' => 'text-success',
                'selector' => 'tr.ajaxImportMarketCategoryConnections',
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxImportBrandsAction(
        \OmniTools\Persistence\Repository\Brand $brandRepository,
    ): ResponseInterface
    {
        $file = CORE_DIR . 'Export.csv';

        // Read a CSV file
        $handle = fopen($file, "r");

        // Optionally, you can keep the number of the line where
        // the loop its currently iterating over
        $lineNumber = 0;
        $loop = 0;

        $imported = [];

        // Iterate over every line of the file
        while (($raw_string = fgets($handle)) !== false) {
            ++$lineNumber;

            // Parse the raw csv string: "1, a, b, c"
            $row = str_getcsv($raw_string, ';');

            if (count($row) == 1) {
                continue;
            }

            if ($lineNumber == 1) {
                continue;
            }

            if (empty($row[8])) {
                continue;
            }

            $title = trim($row[8]);

            if (in_array($title, $imported)) {
                continue;
            }

            // Check brand
            $brand = $brandRepository->fetchOne([
                'where' => [
                    'title' => $title,
                ],
            ]);

            if (empty($brand)) {

                // Insert brand
                $brand = new \OmniTools\Persistence\Entity\Brand([
                    'title' => $title,
                ]);

                $brand = $brandRepository->insert($brand);

                ++$loop;
            }

            $imported[] = $title;
        }

        return new Response([
            'success' => 'Es wurden ' . $loop . ' Marken importiert.',
            'setClasses' => [
                'add' => 'text-success',
                'selector' => 'tr.ajaxImportBrands',
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxImportUsersAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User $userRepository,
        \OmniTools\Persistence\Repository\Brand $brandRepository,
        \OmniTools\Persistence\Repository\Market $marketRepository,
        \OmniTools\Persistence\Repository\Supervisor\Group $groupRepository,

    ): ResponseInterface
    {
        $file = CORE_DIR . 'Export.csv';

        // Read a CSV file
        $handle = fopen($file, "r");

        // Optionally, you can keep the number of the line where
        // the loop its currently iterating over
        $lineNumber = 0;
        $start = ((int) $get->get('lineCurrent')) - 1;
        $break = $start + (int) $get->get('break');

        if ($start < 0) {
            $start = 0;
        }

        $loop = 0;

        // Iterate over every line of the file
        while (($raw_string = fgets($handle)) !== false) {

            ++$lineNumber;

            if ($lineNumber < $start) {
                continue;
            }

            if ($lineNumber >= $break) {
                break;
            }

            // Parse the raw csv string: "1, a, b, c"
            $row = str_getcsv($raw_string, ';');

            if (count($row) == 1) {
                continue;
            }

            if ($lineNumber == 1) {
                continue;
            }

            // Check market
            $market = $marketRepository->fetchOne([
                'where' => [
                    'hpId' => $row[0],
                ],
            ]);

            if (empty($market)) {

                d("MISSING MARKET");
            }

            if (empty($row[10])) {
                continue;
            }

            // Check user
            $user = $userRepository->fetchOne([
                'where' => [
                    'email' => $row[10],
                ],
            ]);

            if (empty($user)) {

                if (!preg_match('#^(.*?),(.*?)\(([a-z]{1})\)$#i', trim($row[9]), $match)) {
                    d($row);
                }

                // Fetch supervisor group
                $group = $groupRepository->fetchOne([
                    'where' => [
                        'shorthand' => $match[3],
                    ]
                ]);

                if (empty($group)) {

                    $group = new \OmniTools\Persistence\Entity\Supervisor\Group([
                        'shorthand' => $match[3],
                    ]);

                    $group = $groupRepository->persist($group);
                }

                $user = new \OmniTools\Persistence\Entity\User([
                    'email' => $row[10],
                    'access' => 'User',
                    'firstName' => trim($match[2]),
                    'lastName' => trim($match[1]),
                    'supervisorGroupId' => $group->getId(),
                ]);

                $user = $userRepository->insert($user);

                ++$loop;
            }

            // Check brand
            $brand = $brandRepository->fetchOne([
                'where' => [
                    'title' => trim($row[8]),
                ],
            ]);

            if (empty($brand)) {
                d($row);
            }

            $market->addUser($user, $brand);
        }

        fclose($handle);

        return new Response([
            'success' => 'Es wurden ' . $loop . ' Benutzer importiert.',
            'currentLine' => $lineNumber,
            'setClasses' => [
                'add' => 'text-success',
                'selector' => 'tr.ajaxImportUsersAction',
            ],
        ]);
    }

    public function ajaxImportSupervisorsAction(
        \OmniTools\Persistence\Repository\Supervisor\Group $groupRepository,
    ): ResponseInterface
    {
        $shorthands = [
            'M' => 'AD Must + Riedel\GVL Hagemann',
            'P' => 'AD Petri\GVL Kistner',
            'G' => 'Exklusive\Greenforce',
            'O' => 'Ehemalige Mitarbeiter\Ehemalige Mitarbeiter extern\Obela',
            'S' => 'Exklusive\Sylter Dressing',
            'K' => 'Exklusive\Kluth',
            'E' => 'Partner\Industriemitarbeiter',
            'B' => 'Exklusive\Borco',
        ];

        foreach ($groupRepository->fetch() as $group) {

            if (empty($shorthands[$group->getShorthand()])) {
                d($group);
            }

            $group->setTitle($shorthands[$group->getShorthand()]);
            $group->save();
        }
    }

    /**
     *
     */
    public function ajaxModalResetAction(

    ): ResponseInterface
    {

        return new Response([

        ]);
    }

    /**
     * @return ResponseInterface
     */
    public function indexAction(

    ): ResponseInterface
    {
        $file = CORE_DIR . 'Export.csv';

        $c =0;
        $fp = fopen($file,"r");
        if($fp){
            while (($raw_string = fgets($fp)) !== false) {
                if($raw_string)
                    $c++;
            }
        }
        fclose($fp);

        return new Response([
            'lines' => $c,
        ]);
    }

}
