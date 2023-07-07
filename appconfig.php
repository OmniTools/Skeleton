<?php
/**
 *
 */

return [
    'config.file' => CORE_DIR . 'localconfig.php',
    \Frootbox\Db\Dbms\Interfaces\Dbms::class => function ( $c ) {

        $config = $c->get(\Frootbox\Config\Config::class);

        switch ($config->get('Database.Dbms')) {
            case 'Mysql':
                return new \Frootbox\Db\Dbms\Mysql(
                    host: $config->get('Database.Host'),
                    schema: $config->get('Database.Schema'),
                    user: $config->get('Database.User'),
                    password: $config->get('Database.Password'),
                );

            default:
                throw new \Exception('Unknown DBMS wrapper: ' . $config->get('Database.Dbms'));
        }
    },
    \Frootbox\Mail\Transports\Interfaces\TransportInterface::class => function ( $c ) {

        $config = $c->get(\Frootbox\Config\Config::class);

        if (class_exists($config->get('mail.transport'))) {
            $class = $config->get('mail.transport');
        }
        else {
            $class = '\\Frootbox\\Mail\\Transports\\' . ucfirst($config->get('mail.transport') ?? 'Smtp');
        }

        $transport = $c->get($class);

        return $transport;
    },
    \Frootbox\MVC\Persistence\Entities\Interfaces\UserInterface::class => function ($c ) {

        if (empty($_SESSION['userId'])) {
            throw new \Exception('Not logged in');
        }

        $userRepository = $c->get(\OmniTools\Persistence\Repository\User::class);

        if (empty($_SESSION['tenantId'])) {

            try {
                $user = $userRepository->fetchById($_SESSION['userId']);
            }
            catch (\Exception $e) {
                unset($_SESSION['userId']);
                die("Benutzer nicht gefunden.");
            }
        }
        else {

            $sql = 'SELECT
                u.*,
                x.role,
                x.roleId,
                r.title as roleTitle
            FROM
                users u,
                realms_users x
            LEFT JOIN
                rbac_roles r
            ON
                r.id = x.roleId
            WHERE
                u.id = :userId AND
                x.userId = u.id AND
                x.realmId = :realmId
            LIMIT 1';

            $result = $userRepository->fetchByQuery($sql, [
                'userId' => $_SESSION['userId'],
                'realmId' => $_SESSION['tenantId'],
            ]);

            $user = $result->current();
        }

        if (empty($_SESSION['skipUserActivity'])) {
            $user->setLastClick(date('Y-m-d H:i:s'));
            $user->save();
        }

        if (!defined('ACCESS_LEVEL')) {
            define('ACCESS_LEVEL', $user->getAccess());
        }

        return $user;
    },

    \OmniTools\Persistence\Entity\User::class => function ($c ) {

        if (empty($_SESSION['userId'])) {
            throw new \Exception('Not logged in');
        }

        $userRepository = $c->get(\OmniTools\Persistence\Repository\User::class);

        if (empty($_SESSION['tenantId'])) {

            try {
                $user = $userRepository->fetchById($_SESSION['userId']);
            }
            catch (\Exception $e) {
                unset($_SESSION['userId']);
                die("Benutzer nicht gefunden.");
            }
        }
        else {

            $sql = 'SELECT
                u.*,
                x.role,
                x.roleId,
                r.title as roleTitle
            FROM
                users u,
                realms_users x
            LEFT JOIN
                rbac_roles r
            ON
                r.id = x.roleId
            WHERE
                u.id = :userId AND
                x.userId = u.id AND
                x.realmId = :realmId
            LIMIT 1';

            $result = $userRepository->fetchByQuery($sql, [
                'userId' => $_SESSION['userId'],
                'realmId' => $_SESSION['tenantId'],
            ]);

            $user = $result->current();
        }

        if (empty($_SESSION['skipUserActivity'])) {
            $user->setLastClick(date('Y-m-d H:i:s'));
            $user->save();
        }

        if (!defined('ACCESS_LEVEL')) {
            define('ACCESS_LEVEL', $user->getAccess());
        }

        return $user;
    },
];
