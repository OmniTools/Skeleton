<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

setlocale (LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
date_default_timezone_set('Europe/Berlin');

require '../vendor/autoload.php';

define('CORE_DIR', realpath(__DIR__ . '/../') . '/');

// Register autoloader
spl_autoload_register(function ($class) {

    if (substr($class, 0, 9) !== 'OmniTools') {
        return;
    }

    $path = substr($class, 10);

    $path = CORE_DIR . 'src/' . $path;
    $path = str_replace('\\', '/', $path) . '.php';

    if (file_exists($path)) {
        require_once $path;
        return;
    }
});

try {

    if (strpos($_SERVER['REQUEST_URI'], 'de/signup') !== false) {
        header('Location: /Session/registration');
        exit;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'de/signin') !== false) {
        header('Location: /');
        exit;
    }

    session_start();

    // Build dependency injection container
    $builder = new \DI\ContainerBuilder();
    $builder->useAnnotations(true);
    $builder->addDefinitions(CORE_DIR . 'appconfig.php');

    $container = $builder->build();

    $configuration = $container->get(\Frootbox\Config\Config::class);

    define('DEV_MODE', !empty($configuration->get('Platform.DevMode')));
    define('PASSWORD_SALT', $configuration->get('passwordSalt'));

    // Setup error logging
    if (!empty($configuration->get('errorlogging.Sentry.dsn'))) {

        \Sentry\init([
            'dsn' => $configuration->get('errorlogging.Sentry.dsn'),
            'environment' => $configuration->get('errorlogging.Sentry.environment') ?? 'development',
            'release' => \OmniTools\App::$Version,
        ]);

        if (!empty($_SESSION['userId'])) {

            \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                $scope->setContext('user', [
                    'id' => $_SESSION['userId'],
                ]);
            });
        }
    }


    $translator = $container->get(\Frootbox\MVC\Translator::class);
    $translator->addResource(CORE_DIR . 'resources/private/language/de-DE.php');

    $view = $container->get(\Frootbox\MVC\View::class);
    $view->assign('configuration', $configuration);

    $filter = new \Twig\TwigFilter('gettype', function ($string) {
        return gettype($string);
    });
    $view->addFilter($filter);

    $filter = new \Twig\TwigFilter('dump', function ($string) {
        d($string);
    });
    $view->addFilter($filter);

    $filter = new \Twig\TwigFilter('translate', function ($string) use ($translator) {
        return $translator->translate($string);
    });
    $view->addFilter($filter);

    $filter = new \Twig\TwigFilter('md5', function ($string) use ($translator) {

        return md5($string);
    });
    $view->addFilter($filter);

    $filter = new \Twig\TwigFilter('tokenize', function ($string) {
        return md5($string . '#' . PASSWORD_SALT);
    });
    $view->addFilter($filter);

    $filter = new \Twig\TwigFilter('filesize', function ($string) {

        $bytes = (int) $string;

        if ($bytes < 1000) {
            return $bytes . ' B';
        }

        $kilobytes = $bytes / 1000;

        if ($kilobytes < 1000) {
            return round($kilobytes, 2) . ' KB';
        }

        $megabytes = $kilobytes / 1000;

        if ($megabytes < 1000) {
            return round($megabytes, 2) . ' MB';
        }

        $gigabytes = $megabytes / 1000;

        return round($gigabytes, 2) . ' GB';
    });
    $view->addFilter($filter);


    if (!empty($_SESSION['userId'])) {

        $user = $container->get(\OmniTools\Persistence\Entity\User::class);

        if ($user->getAccess() == 'User') {

        }

        $view->assign('user', $user);
    }

    set_exception_handler(function($exception) {

        if ($exception instanceof \Twig\Error\RuntimeError) {
            if ($exception->getPrevious() and $exception->getPrevious() instanceof \OmniTools\Exceptions\TenantUnset) {
                $redirect = strpos(ORIGINAL_REQUEST, 'ajax') === false ? ORIGINAL_REQUEST : $_SERVER['HTTP_REFERER'];

                $url = SERVER_PATH . 'Session/tenantSelect?return=' . urlencode($redirect) . '&referer=' . urlencode($_SERVER['HTTP_REFERER']);

                if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                    die(json_encode([
                        'redirect' => $url,
                    ]));
                }

                header('Location: ' . $url);
                exit;
            }
        }

        throw $exception;
    });

    $view = $container->get(\Frootbox\MVC\View::class);


    $view->assign('version', App::$Version);
    $view->assign('front', new \Frootbox\MVC\View\Front());

    $dispatcher = new \Frootbox\MVC\Dispatcher($container, [
        'namespace' => 'OmniTools',
        'baseDir' => 'xxxx',
        'cachepath' => CORE_DIR . 'files/cache/',
    ]);

    $controller = $dispatcher->getControllerFromRequest();

    $response = $controller->execute();

    die($response->getBody());
}
catch (\OmniTools\Exceptions\PlanMissing $exception) {
    header('Location: ' . $controller->getUri('Account/Obligations'));
    exit;
}
catch (\OmniTools\Exceptions\SubscriptionExpired $exception) {

    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        die(json_encode([
            'redirect' => $controller->getUri('Account/Plan', 'expired'),
        ]));
    }

    header('Location: ' . $controller->getUri('Account/Plan', 'expired'));
    exit;
}
catch (\Frootbox\Exceptions\InputMissing $e) {

    $message = $e->getMessage();

    if ($message == 'Field') {

        if (!empty($translator)) {
            $message = $translator->translate('ExceptionFieldsMissing');
        }

        /*
        $properties = $e->getProperties();

        if (preg_match('#^T:(.*?)$#', $properties[0], $match)) {
            $key = 'Field' . ucfirst($match[1]);

            if (!empty($translator)) {
                $key = $translator->translate($key);
                $message = $translator->translate('ExceptionFieldMissing');

                $message = sprintf($message, $key);
            }
        }
        */
    }

    http_response_code(500);

    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        die(json_encode([
            'error' => $message,
        ]));
    }

    die('Fatal error: ' . $message);
}
catch (\Frootbox\Exceptions\AccessDenied $exception) {

    http_response_code(500);

    $message = !empty($exception->getMessage()) ? $exception->getMessage(): 'Zugriff verweigert.';

    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        die(json_encode([
            'error' => $message,
        ]));
    }

    header('Location: ' . SERVER_PATH . 'Session/login');
    exit;
}
catch (\Frootbox\Exceptions\NotFound $exception) {

    http_response_code(404);

    // Log error
    \Sentry\captureException($exception);

    // Render error page
    $viewFile = CORE_DIR . 'resources/private/views/Error/404.html.twig';
    $source = $view->render($viewFile);

    die($source);
}
catch (\Exception $exception) {

    http_response_code(500);

    if (!empty($_SESSION['adminId']) or (defined('DEV_MODE') and DEV_MODE) or (isset($user) and $user->getAccess() == 'Superuser')) {
        $exposeError = true;
    }
    else {
        $exposeError = false;
    }

    if ($exception instanceof \Frootbox\Exceptions\InputInvalid) {
        $logError = false;
        $viewFile = CORE_DIR . 'resources/private/views/Error/500Public.html.twig';
        $exposeError = false;
    }
    else {
        $logError = true;
        $viewFile = CORE_DIR . 'resources/private/views/Error/500.html.twig';
    }

    // Log error
    if ($logError) {
        $eventId = \Sentry\captureException($exception);
    }
    else {
        $eventId = null;
    }

    $extend = null;

    if (!empty($_SERVER['HTTP_ACCEPT']) and strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        die(json_encode([
            'error' => !empty($exception->getMessage()) ? $exception->getMessage() : get_class($exception),
        ]));
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        $extend = 'Bare.html.twig';
    }

    if ($exception instanceof \Twig\Error\RuntimeError) {
        $exception = $exception->getPrevious();
    }

    if (!isset($view)) {
        die($exception->getMessage());
    }

    // Render error page
    $source = $view->render($viewFile, [
        'exception' => $exception,
        'exposeError' => $exposeError,
        'extend' => $extend,
        'eventId' => (string) $eventId,
    ]);

    die($source);
}
