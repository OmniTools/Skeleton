<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Admin\Users;

use Frootbox\MVC\ResponseInterface;
use Frootbox\MVC\Response;
use Frootbox\MVC\ResponseRedirect;

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
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @return ResponseInterface
     */
    public function activityAction(
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch users
        $users = $userRepository->fetch([
            'where' => [
                'access' => 'User',
            ],
            'limit' => 100,
            'order' => [ 'lastClick DESC' ],
        ]);

        return new Response([
            'users' => $users,
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \OmniTools\Persistence\Repository\User\Role $roleRepository
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxModalComposeAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): ResponseInterface
    {
        // Fetch role
        $role = $roleRepository->fetchById($get->get('roleId'));

        return new Response([
            'role' => $role,
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxCaptureLoginAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        if (!empty($user->getCompanyId())) {
            $_SESSION['companyId'] = $user->getCompanyId();
        }
        else {
            unset($_SESSION['companyId']);
        }

        $_SESSION['adminId'] = $_SESSION['userId'];
        $_SESSION['userId'] = $user->getId();
        $_SESSION['skipUserActivity'] = true;

        return new ResponseRedirect($this->getUri('Dashboard', 'index'));
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @param \Frootbox\MVC\View $view
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\InputInvalid
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User $userRepository,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {
        $post->requireOne([ 'email', 'multiEmail' ]);

        // Fetch role
        $role = $roleRepository->fetchById($get->get('roleId'));

        if (!empty($post->get('multiEmail'))) {
            $emails = explode("\n", $post->get('multiEmail'));
        }
        else {
            $emails = [ $post->get('email') ];
        }

        foreach ($emails as $email) {

            $email = trim($email);

            if (empty($email)) {
                continue;
            }

            // Compose new user
            $user = new \OmniTools\Persistence\Entity\User([
                'email' => $email,
            ]);

            // Insert user
            $user = $userRepository->persist($user);

            // Add role to user
            $user->roleAdd($role);
        }

        if (!empty($get->get('redirect'))) {

            Front::success('Der Benutzer wurde erstellt.');

            if ($get->get('redirect') == 'userdetails') {
                return new ResponseRedirect($this->getActionUri('details', [
                    'userId' => $user->getId(),
                ]));
            }
            else {
                return new ResponseRedirect($get->get('redirect'));
            }
        }

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#role-receiver-' . $role->getId(),
                'html' => $view->partial('OmniTools/Controller/Admin/Users/Partials/ListUsers', [
                    'role' => $role,
                    'highlight' => $user->getId(),
                ]),
            ],
        ]);
    }

    /**
     * @param \Frootbox\Db\Db $db
     * @param \Frootbox\Http\Get $get
     * @param \OmniTools\Persistence\Repository\Log $logRepository
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxDeleteAction(
        \Frootbox\Db\Db $db,
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\Log $logRepository,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        $db->transactionStart();

        // Log action
        $logRepository->logActionForUser($user, 'UserDeleted', [
            'user' => $user->getData(),
        ]);

        // Delete user
        $user->delete();

        $db->transactionCommit();

        if ($get->get('return') == 'fadeOut') {

            return new Response([
                'success' => 'Der Benutzer wurde gelöscht.',
                'fadeOut' => '[data-user="' . $user->getId() . '"]',
            ]);
        }

        \Frootbox\MVC\View\Front::success('Der Benutzer wurde gelöscht.');

        return new ResponseRedirect($this->getActionUri('index', [ 'access' => $user->getAccess() ]));
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @return ResponseInterface
     */
    public function ajaxExportAction(
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch users
        $result = $userRepository->fetch([
            'where' => [
                'access' => 'User',
                new \Frootbox\Db\Conditions\GreaterOrEqual('date', $post->get('dateStart')),
                new \Frootbox\Db\Conditions\LessOrEqual('date', $post->get('dateEnd')),
            ],
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'prefix');
        $f = fopen($tmpFile, 'w');

        $line = [
            'Kd.-Nr.',
            'E-Mail',
            'Vorname',
            'Nachname',

            'Firma',
            'Straße',
            'PLZ',
            'Ort',
            'URL',
            'Mobil',
            'Telefon',
        ];

        fputcsv($f, $line, ';');

        foreach ($result as $user) {

            $company = $user->getCompany();

            if (!empty($company)) {
                $location = $company->getLocationBilling();
            }

            $line = [
                $company?->getCustomerNumber(),
                $user->getEmail(),
                $user->getFirstname(),
                $user->getLastname(),
                $company?->getTitle(),
                $location?->getStreet(),
                $location?->getZipcode(),
                $location?->getCity(),
                $company?->getUrl(),
                $user->getPhone(),
                $user->getMobile(),
            ];


            fputcsv($f, $line, ';');
        }

        fseek($f, 0);

        ob_end_clean();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export-' . date('Y-m-d-H-i-s') . '.csv";');

        echo file_get_contents($tmpFile);
        exit;
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param Mailer $mailer
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxMailSendPredefinedAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Mailer $mailer,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        // Compose mail
        $envelope = new \Frootbox\Mail\Envelope();
        $envelope->addTo($user->getEmail(), $user->getName());

        $mailer->send($envelope, $get->get('mailKey') . '.html.twig', [
            'xuser' => $user,
            'url' => $this->getUri('Session', 'login', [
                'email' => $user->getEmail(),
            ]),
        ]);

        $user->addConfig([
            'mails' => [
                $get->get('mailKey') => date('Y-m-d H:i:s'),
            ],
        ]);

        $user->save();

        return new Response([
            'success' => 'Die E-Mail wurde gesendet.',
        ]);
    }

    public function ajaxModalDeleteAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        return new Response([
            'xuser' => $user,
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @param \Frootbox\MVC\View $view
     * @return ResponseInterface
     */
    public function ajaxSearchAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User $userRepository,
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {
        $sortColumn =  !empty($post->get('sortColumn')) ? $post->get('sortColumn') : 'email';
        $sortDirection = !empty($post->get('sortDirection')) ? $post->get('sortDirection') : 'ASC';

        // Build sql
        $sql = 'SELECT * FROM users
        WHERE
            (
                email LIKE :q OR
                firstname LIKE :q OR 
                lastname LIKE :q OR
                id = ' . (int)$post->get('keyword') . '
            )
            AND
            access = :access
        ORDER BY
            ' . $sortColumn . ' '  . $sortDirection;

        $users = $userRepository->fetchByQuery($sql, [
            'q' => '%' . $post->get('keyword') . '%',
            'access' => $get->get('access'),
        ]);

        return new Response([
            'replace' => [
                'selector' => $get->get('selector'),
                'html' => $view->partial('OmniTools/Controller/Admin/Users/Partials/ListUsers', [
                    'access' => $get->get('access'),
                    'result' => $users,
                    'selector' => $get->get('selector'),
                    'keyword' => $post->get('keyword'),
                ]),
            ],
        ]);
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @param \Frootbox\MVC\View $view
     * @return ResponseInterface
     */
    public function ajaxSearchAvailabilitiesAction(
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User $userRepository,
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {

        // Generate sql
        $sql = 'SELECT ';


        if (!empty($post->get('lat')) and !empty($post->get('lng'))) {
            $sql .= ' ST_Distance_Sphere(point(' . $post->get('lng') . ',' . $post->get('lat') . '), point(l.lng, l.lat)) as distance, ';
        }

        $sql .= ' e.id,
            e.firstname,
            e.lastname,
            e.companyId,
            av.dateStart,
            av.dateEnd,
            av.locationId,
            ab.tradeId,
            ab.experienceLevel,
            l.title as locationTitle,
            l.zipcode as locationPostalCode,
            l.street as locationStreet,
            t.title as tradeTitle,
            tab.title as abilityTitle,
            twa.title as workareaTitle,
            tpr.title as productTitle,
            tma.title as manufacturerTitle
        FROM
            employees_availabilities av,            
            employees e,
            trades t,
            companies_locations l,
            employees_abilities ab
        LEFT JOIN
            trades_products tpr
        ON
            ab.productId = tpr.id
        LEFT JOIN
            trades_abilities tab
        ON
            ab.abilityId = tab.id
        LEFT JOIN
            trades_workareas twa
        ON
            ab.workareaId = twa.id
        LEFT JOIN
            trades_manufacturers tma
        ON
            ab.manufacturerId = tma.id
        WHERE
            l.id = av.locationId AND
            av.employeeId = e.id AND
            av.employeeId = ab.employeeId AND
            ab.tradeId = t.id ';

        if (!empty($post->get('dateFrom'))) {
            $sql .= ' AND av.dateStart >= "' . $post->get('dateFrom') . '" ';
        }

        if (!empty($post->get('dateTo'))) {
            $sql .= ' AND av.dateEnd <= "' . $post->get('dateTo') . '" ';
        }
        else {
            $sql .= ' AND av.dateEnd > "' . date('Y-m-d') . '" ';
        }


        if (!empty($post->get('distanceMin')) or !empty($post->get('distanceMin'))) {

            $sql .= ' HAVING ';

            if (!empty($post->get('distanceMin'))) {
                $sql .= ' distance >= ' . ($post->get('distanceMin') * 1000) . ' ';
            }

            if (!empty($post->get('distanceMax'))) {

                if (!empty($post->get('distanceMin'))) {
                    $sql .= ' AND ';
                }

                $sql .= ' distance <= ' . ($post->get('distanceMax') * 1000) . ' ';
            }
        }

        $sql .= ' ORDER BY ';

        if (!empty($post->get('lat')) and !empty($post->get('lng'))) {
            $sql .= ' distance ASC, ';
        }

        $sql .= '
            e.id DESC,
            tradeTitle ASC,
            manufacturerTitle ASC,
            productTitle ASC,
            workareaTitle ASC,
            abilityTitle ASC
        ';

        // Fetch results
        $result = $userRepository->fetchByQuery($sql);

        return new Response([
            'replace' => [
                'selector' => '#availabilities-receiver',
                'html' => $view->partial('OmniTools/Controller/Admin/Users/Partials/SearchAvailabilities', [
                    'result' => $result,
                    'skipDistance' => (empty($post->get('lat')) or empty($post->get('lng'))),
                ]),
            ],
        ]);
    }

    /**
     * @param \Frootbox\MVC\View $view
     * @return ResponseInterface
     */
    public function ajaxShowActivityLogAction(
        \Frootbox\MVC\View $view,
    ): ResponseInterface
    {
        return new Response([
            'replace' => [
                'selector' => '#log-receiver',
                'html' => $view->partial('OmniTools/Controller/Admin/Users/Partials/ListActivityLogs'),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\Log $logRepository,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        // Update user
        $user->trackChanges();

        if (!empty($post->get('Email')) and $user->getEmail() != $post->get('Email')) {

            // Check if email exists
            $check = $userRepository->fetchOne([
                'where' => [
                    'email' => $post->get('Email'),
                ],
            ]);

            if ($check !== null) {
                throw new \Exception('Diese E-Mail Adresse ist bereits in Verwendung.');
            }
        }

        if (!empty($post->get('Login')) and $user->getLogin() != $post->get('Login')) {

            // Check if email exists
            $check = $userRepository->fetchOne([
                'where' => [
                    'login' => $post->get('Login'),
                ],
            ]);

            if ($check !== null) {
                throw new \Exception('Dieser Benutzername ist bereits in Verwendung.');
            }
        }

        $user->setEmail($post->get('Email'));
        $user->setLogin($post->get('Login'));
        $user->setFirstName($post->get('FirstName'));
        $user->setLastName($post->get('LastName'));

        $user->setPhone($post->get('Phone'));
        $user->setMobile($post->get('Mobile'));

        $user->setStreet($post->get('Street'));
        $user->setZipcode($post->get('Zipcode'));
        $user->setCity($post->get('City'));

        if (!empty($post->get('Password'))) {
            $user->setPassword($post->get('Password'));
        }

        // Log action
        $logRepository->logActionForUser($user, 'UserUpdated', [
            'changes' => $user->trackChanges(),
        ]);

        $user->save();

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateDistributionAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        $user->setSalesmanId($post->get('salesmanId'));
        $user->save();

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @param \OmniTools\Persistence\Repository\User\Role $roleRepository
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxUpdateRolesAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User $userRepository,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        // Fetch roles
        $roles = $roleRepository->fetch();

        $selectedRoleIds = $post->get('roles');

        foreach ($roles as $role) {

            if (isset($selectedRoleIds[$role->getId()])) {

                // Add role
                $user->roleAdd($role);
            }
            else {

                // Remove role
                $user->roleRemove($role);
            }
        }

        return new Response([
            'success' => 'Die Daten wurden gespeichert.',
        ]);
    }

    /**
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @return ResponseInterface
     */
    public function availabilitiesAction(
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Generate sql
        $sql = 'SELECT
            e.id,
            e.firstname,
            e.lastname,
            e.companyId,
            av.dateStart,
            av.dateEnd,
            av.locationId,
            ab.tradeId,
            ab.experienceLevel,
            t.title as tradeTitle,
            tab.title as abilityTitle,
            twa.title as workareaTitle,
            tpr.title as productTitle,
            tma.title as manufacturerTitle
        FROM
            employees_availabilities av,            
            employees e,
            trades t,
            employees_abilities ab
        LEFT JOIN
            trades_products tpr
        ON
            ab.productId = tpr.id
        LEFT JOIN
            trades_abilities tab
        ON
            ab.abilityId = tab.id
        LEFT JOIN
            trades_workareas twa
        ON
            ab.workareaId = twa.id
        LEFT JOIN
            trades_manufacturers tma
        ON
            ab.manufacturerId = tma.id
        WHERE
            av.dateEnd > "' . date('Y-m-d') . '" AND 
            av.employeeId = e.id AND
            av.employeeId = ab.employeeId AND
            ab.tradeId = t.id
        ORDER BY
            e.id ASC,
            tradeTitle ASC,
            manufacturerTitle ASC,
            productTitle ASC,
            workareaTitle ASC,
            abilityTitle ASC';

        $result = $userRepository->fetchByQuery($sql);

        return new Response([
            'rows' => $result,
        ]);
    }

    /**
     *
     */
    public function deleteAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        return new Response([
            'xuser' => $user,
            'company' => $user->getCompany(),
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @param \OmniTools\Persistence\Repository\User\Role $roleRepository
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User $userRepository,
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        // Fetch roles
        $roles = $roleRepository->fetch();

        return new Response([
            'xuser' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * @return ResponseInterface
     */
    public function exportAction(

    ): ResponseInterface
    {
        return new Response;
    }

    /**
     * @param \OmniTools\Persistence\Repository\User\Role $roleRepository
     * @return ResponseInterface
     */
    public function indexAction(
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): ResponseInterface
    {
        // Fetch roles (reverse order)
        $roles = $roleRepository->fetch([
            'order' => [ 'orderId ASC' ],
        ]);

        return new Response([
            'roles' => $roles,
        ]);
    }

    public function importAction(
        \OmniTools\Persistence\Repository\User\Role $roleRepository,
    ): ResponseInterface
    {
        // Fetch roles (reverse order)
        $roles = $roleRepository->fetch([
            'order' => [ 'orderId ASC' ],
        ]);

        return new Response([
            'roles' => $roles,
        ]);
    }

    /**
     *
     */
    public function loginUrlAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        // Crete signed action
        $signedAction = $user->signedActionCreate('AdminLoginOverride', [
            'preventDuplicates' => true,
        ]);

        return new Response([
            'signedAction' => $signedAction,
            'xuser' => $user,
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \OmniTools\Persistence\Repository\User $userRepository
     * @return ResponseInterface
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function logsAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        return new Response([
            'xuser' => $user,
            'company' => $user->getCompany(),
        ]);
    }

    public function mailAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchById($get->get('userId'));

        $preDefinedMails = [
            'RegisteredReminder1',
        ];

        return new Response([
            'xuser' => $user,
            'company' => $user->getCompany(),
            'preDefinedMails' => $preDefinedMails,
        ]);
    }
}
