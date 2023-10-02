<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace OmniTools\Controller\Session;

use Frootbox\Db\Db;
use OmniTools\Persistence\Entity\User;
use Frootbox\MVC\Response;
use Frootbox\MVC\ResponseInterface;
use Frootbox\MVC\ResponseRedirect;
use OmniTools\Token;

/**
 * @access Public
 */
class Controller extends \Frootbox\MVC\AbstractController
{
    /**
     * Generate menu
     */
    public function getMenuForAction(): ?\OmniTools\View\Menu
    {
        $get = $this->container->get(\Frootbox\Http\Get::class);

        $menu = new \Avaro\View\Menu([
            [
                'title' => 'Übersicht',
                'items' => [
                    new \Avaro\View\Menu\Item(
                        title: 'Profil bearbeiten',
                        url: 'Session/profile',
                        icon: 'user',
                    ),
                ],
            ],
        ]);

        return $menu;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $password
     * @return void
     * @throws \Frootbox\Exceptions\InputInvalid
     */
    private function validatePassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new \Frootbox\Exceptions\InputInvalid(sprintf('Das Passwort muss mindestens %s Zeichen lang sein.', 8));
        }

        if (!preg_match('#([a-z])#i', $password)) {
            throw new \Frootbox\Exceptions\InputInvalid('Das Passwort muss mindestens einen Buchstaben enthalten.');
        }

        if (!preg_match('#([0-1])#i', $password)) {
            throw new \Frootbox\Exceptions\InputInvalid('Das Passwort muss mindestens eine Zahl enthalten.');
        }
    }

    /**
     * @access Public
     */
    public function ajaxActivationRequestAction(
        \Frootbox\Http\Post $post,
        \OmniTools\Mailer $mailer,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch target user
        $user = $userRepository->fetchOne([
            'where' => [
                'email' => $post->get('email'),
            ],
        ]);

        if ($user and $user->getState() == 'Created') {

            // Crete signed action
            $signedAction = $user->signedActionCreate('AccountActivate');

            // Compose mail
            $envelope = new \Frootbox\Mail\Envelope();
            $envelope->setSubject('Account Aktivierung');
            $envelope->addTo($user->getEmail(), $user->getName());

            $mailer->send($envelope, 'AccountActivation.html.twig', [
                'xuser' => $user,
                'url' => $this->getActionUri('accountActivate', [
                    'email' => $user->getEmail(),
                    'token' => $signedAction->getToken(),
                ]),
            ]);
        }

        return new \Frootbox\MVC\Response([
            'success' => 'Sofern ein Benutzer mit dieser E-Mail existiert wurde ein Link zur Aktivierung per E-Mail versendet.',
        ]);
    }

    /**
     * @access Public
     */
    public function ajaxCheckLoginAction(
        \Frootbox\MVC\Session $session,
    ): ResponseInterface
    {
        return new Response([
            'isLoggedIn' => $session->isLoggedIn(),
        ]);
    }

    /**
     * @access Public
     */
    public function ajaxLoginAction(
        \Frootbox\Db\Db $db,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\MVC\Session $session,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        if (filter_var($post->get('login'), FILTER_VALIDATE_EMAIL)) {

            // Fetch target user
            $user = $userRepository->fetchOne([
                'where' => [
                    'email' => $post->get('login'),
                ],
            ]);
        }
        else {

            // Fetch target user
            $user = $userRepository->fetchOne([
                'where' => [
                    'login' => $post->get('login'),
                ],
            ]);
        }


        if (!$user) {

            // Check if there is any user
            $check = $userRepository->fetchOne();

            if (!empty($check)) {
                throw new \Frootbox\Exceptions\InputMissing('Anmeldung fehlgeschlagen.');
            }

            $db->transactionStart();

            // Create super-user on empty database
            $user = $userRepository->persist(new \OmniTools\Persistence\Entity\User([
                'email' => $post->get('login'),
                'access' => 'Superuser',
            ]));

            $user->setPassword($post->get('password'));
            $user->save();

            $db->transactionCommit();
        }

        // Log in user
        $session->login($user);

        if ($get->get('proceed') == 'modalDismiss') {
            return new \Frootbox\MVC\Response([
                'modalDismiss' => true,
            ]);
        }

        return new \Frootbox\MVC\ResponseRedirect('Dashboard');
    }

    /**
     * @access Public
     */
    public function ajaxPasswordChangeAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        if (empty($post->get('pw1'))) {
            throw new \Frootbox\Exceptions\InputInvalid('Bitte alle Felder ausfüllen.');
        }

        // Fetch user
        $user = $userRepository->fetchOne([
            'where' => [
                'email' => $get->get('email'),
            ],
        ]);

        try {
            $user->signedActionVerify('ChangePassword', $get->get('token'));
        }
        catch (\Frootbox\Exceptions\InputInvalid $exception) {
            return new ResponseRedirect($this->getActionUri('actionFailed'));
        }

        if ($post->get('pw1') != $post->get('pw2')) {
            throw new \Frootbox\Exceptions\InputInvalid('Die Passwörter stimmen nicht überein.');
        }

        $user->setPassword($post->get('pw1'));
        $user->save();

        return new Response([
            'redirect' => $this->getActionUri('login'),
        ]);
    }

    /**
     * @access Public
     */
    public function ajaxPasswordRequestAction(
        \Frootbox\Http\Post $post,
        \OmniTools\Mailer $mailer,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchOne([
            'where' => [
                'email' => $post->get('email'),
            ],
        ]);

        if ($user) {

            // Crete signed action
            $signedAction = $user->signedActionCreate('ChangePassword');

            // Compose mail
            $envelope = new \Frootbox\Mail\Envelope();
            $envelope->setSubject('Neues Passwort angefordert.');
            $envelope->addTo($user->getEmail(), $user->getName());

            $url = $this->getActionUri('passwordSet', [
                'email' => $user->getEmail(),
                'token' => $signedAction->getToken(),
            ]);

            $mailer->send($envelope, 'GeneralPurpose.html.twig', [
                'message' => '    <p>Du hast ein neues Passwort angefordert. Klicke hier, um es zu setzen:</p><p><a href="' . $url . '">Neues Passwort setzen!</a></p>',
            ]);
        }

        return new Response([
            'redirect' => $this->getActionUri('passwordRequested'),
        ]);
    }

    /**
     * @access Public
     */
    public function ajaxRegistrateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \OmniTools\Mailer $mailer,
        \OmniTools\Persistence\Repository\Log $logRepository,
        \OmniTools\Persistence\Repository\User $userRepository,
        \OmniTools\Persistence\Repository\Company $companyRepository,
        \OmniTools\Persistence\Repository\Employee $employeeRepository,
        \OmniTools\Persistence\Repository\User\Consent $consentRepository,
        \OmniTools\Persistence\Repository\Company\Location $locationRepository,
        \OmniTools\Persistence\Repository\Company\Promotion $promotionsRepository,
        \Frootbox\Db\Db $db,
    ): ResponseInterface
    {
        // Store form data for later autocompletion
        $_SESSION['registration']['autocomplete'] = $post->getData();

        // Check required fields
        $post->require([
            'email',
            'firstname',
            'lastname',
            'recommendationLabelId',
            'company',
            'password',
            'password2',
            'mobile',
        ]);

        if ($post->get('password') != $post->get('password2')) {
            throw new \Frootbox\Exceptions\InputInvalid('Die Passwörter stimmen nicht überein.');
        }

        // Check re-captcha
        $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';

        // Make and decode POST request:
        $recaptcha = file_get_contents($recaptchaUrl . '?secret=' . $config->get('google.recaptcha.secret') . '&response=' . $post->get('recaptchaResponse'));
        $recaptcha = json_decode($recaptcha, true);

        if (empty($recaptcha['success']) or !isset($recaptcha['score']) or $recaptcha['score'] < 0.5) {
            throw new \Frootbox\Exceptions\InputInvalid('Die Registratur konnte nicht abgeschlossen werden.');
        }

        // Check country
        if ($post->get('country') == 'OtherCountry') {

            // Log action
            $logRepository->logAction('CompanyRegisteredOtherCountry', [
                'registration' => $post->getData(),
            ]);

            return new ResponseRedirect('Session/country');
        }

        // Check mobile number
        $mobile = ltrim($post->get('mobile'), '0');

        if ($mobile[0] == '+') {

            // Try to autocorrect
            foreach ($this->countryCodes as $countryCode) {

                if (str_starts_with($mobile, $countryCode)) {
                    $mobile = substr($mobile, strlen($countryCode));
                }
            }

            if ($mobile[0] == '+') {
                throw new \Frootbox\Exceptions\InputInvalid('Bitte geben Sie die Mobilfunknummer ohne Ländervorwahl ein.');
            }
        }

        // Check if user exists
        $check = $userRepository->fetchOne([
            'where' => [
                'email' => $post->get('email'),
            ],
        ]);

        if ($check) {
            throw new \Frootbox\Exceptions\InputMissing('Ein Benutzer mit dieser E-Mail besteht bereits.');
        }

        // Transaction start
        $db->transactionStart();

        // Compose new user
        $user = new \OmniTools\Persistence\Entity\User([
            'email' => $post->get('email'),
            'access' => 'User',
            'firstname' => $post->get('firstname'),
            'lastname' => $post->get('lastname'),
            'gender' => $post->get('gender'),
            'recommendationLabelId' => $post->get('recommendationLabelId'),

            'phone' => $post->get('phone'),
            'mobile' => $post->get('mobilePrefix') . $mobile,

            'street' => $post->get('street'),
            'streetNumber' => $post->get('streetNumber'),
            'zipcode' => $post->get('zipcode'),
            'city' => $post->get('city'),

            'acceptedGeneralterms' => date('Y-m-d H:i:s'),
            'acceptedPrivacy' => date('Y-m-d H:i:s'),
        ]);

        $user->setPassword($post->get('password'));

        $userRepository->insert($user);

        // Compose new company
        $company = new \OmniTools\Persistence\Entity\Company([
            'userId' => $user->getId(),
            'title' => $post->get('company'),
        ]);

        $company = $companyRepository->insert($company);

        // Insert default location
        if (!empty($post->get('lat')) and !empty($post->get('lng'))) {

            $location = $locationRepository->insert(new \OmniTools\Persistence\Entity\Company\Location([
                'userId' => $user->getId(),
                'companyId' => $company->getId(),
                'title' => 'Hauptsitz',
                'street' => $post->get('street') . ' ' . $post->get('streetNumber'),
                'zipcode' => $post->get('zipcode'),
                'city' => $post->get('city'),
                'lat' => $post->get('lat'),
                'lng' => $post->get('lng'),
            ]));
        }

        // Insert default employee
        $employee = new \OmniTools\Persistence\Entity\Employee([
            'companyId' => $company->getId(),
            'userId' => $user->getId(),
            'gender' => $user->getGender(),
            'firstname' => $post->get('firstname'),
            'lastname' => $post->get('lastname'),
            'qualification' => 'Other',
        ]);

        if (!empty($location)) {
            $employee->setLocation($location);
        }

        $employeeRepository->insert($employee);

        // Consent to newsletter
        $consentRepository->insert(new \OmniTools\Persistence\Entity\User\Consent([
            'userId' => $user->getId(),
            'type' => 'Newsletter',
            'consent' => 'Granted',
        ]));

        // Capture promotion
        if (!empty($_SESSION['registration']['promoId'])) {

            // Fetch promoter
            $promoter = $companyRepository->fetchById($_SESSION['registration']['promoId']);

            // Compose new promotion
            $promotion = new \OmniTools\Persistence\Entity\Company\Promotion([
                'companyId' => $promoter->getId(),
                'referrerId' => $company->getId(),
            ]);

            $promotionsRepository->insert($promotion);
        }

        // Crete signed action
        $signedAction = $user->signedActionCreate('AccountActivate');

        // Compose mail
        $envelope = new \Frootbox\Mail\Envelope();
        $envelope->setSubject('Willkommen bei Petri Vertrieb');
        $envelope->addTo($user->getEmail(), $user->getName());

        $mailer->send($envelope, 'Registered.html.twig', [
            'xuser' => $user,
            'url' => $this->getActionUri('accountActivate', [
                'email' => $user->getEmail(),
                'token' => $signedAction->getToken(),
            ]),
        ], [
            // 'adminCopy' => true,
        ]);


        // Log action
        $logRepository->logActionForCompany($company, 'CompanyRegistration', [
            'company' => $company->getData(),
        ]);

        // Log action
        $logRepository->logActionForUser($user, 'UserRegistration', [
            'user' => $user->getData(),
        ]);

        // Transaction commit
        $db->transactionCommit();

        return new ResponseRedirect('Session/registered');
    }

    /**
     * @access Public
     */
    public function ajaxSubmitWaitingListAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \OmniTools\Persistence\Repository\ListEntry $listEntryRepository,
    ): ResponseInterface
    {
        $post->require([ 'email', 'country' ]);

        // Check re-captcha
        $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';

        // Make and decode POST request:
        $recaptcha = file_get_contents($recaptchaUrl . '?secret=' . $config->get('google.recaptcha.secret') . '&response=' . $post->get('recaptchaResponse'));
        $recaptcha = json_decode($recaptcha, true);

        if (empty($recaptcha['success']) or !isset($recaptcha['score']) or $recaptcha['score'] < 0.5) {
            throw new \Frootbox\Exceptions\InputInvalid('Die Registratur konnte nicht abgeschlossen werden.');
        }

        // Check for doublets
        $check = $listEntryRepository->fetchOne([
            'where' => [
                'email' => $post->get('email'),
                'value1' => $post->get('country'),
            ],
        ]);

        if (empty($check)) {

            // Insert new entry
            $listEntry = new \OmniTools\Persistence\Entity\ListEntry([
                'email' => $post->get('email'),
                'type' => 'CountryRequest',
                'value1' => $post->get('country'),
            ]);

            $listEntryRepository->insert($listEntry);
        }

        return new ResponseRedirect($this->getActionUri('countrySubmitted'));
    }

    /**
     *
     */
    public function ajaxSwitchToAdminAction(

    ): ResponseInterface
    {
        if (!empty($_SESSION['adminId'])) {
            $_SESSION['userId'] = $_SESSION['adminId'];
        }

        return new ResponseRedirect($this->getUri('Admin'));
    }

    /**
     * @access Public
     */
    public function ajaxModalLoginAction(

    ): ResponseInterface
    {
        return new Response([

        ]);
    }

    /**
     * @access Public
     */
    public function accountActivateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchOne([
            'where' => [
                'email' => $get->get('email'),
            ],
        ]);

        try {
            $user->signedActionVerify('AccountActivate', $get->get('token'));
        }
        catch (\Frootbox\Exceptions\InputInvalid $exception) {
            return new ResponseRedirect($this->getActionUri('actionFailed'));
        }

        // Activate user
        $user->setState('Approved');
        $user->save();

        return new ResponseRedirect('Session/login?email=' . $user->getEmail());
    }

    /**
     * @access Public
     */
    public function actionFailedAction(): ResponseInterface
    {
        return new Response;
    }

    /**
     * @access Public
     */
    public function captureSignedActionAction(
        \Frootbox\Http\Get $get,
        \Frootbox\MVC\Session $session,
        \OmniTools\Persistence\Repository\User\SignedAction $signedActionRepository,
    ): ResponseInterface
    {
        // Fetch signed action
        $signedAction = $signedActionRepository->fetchOne([
            'where' => [
                'userId' => $get->get('uid'),
                'action' => $get->get('action'),
                'token' => $get->get('token'),
            ],
        ]);

        if (empty($signedAction)) {
            throw new \Exception('SignedActionMissing');
        }

        switch ($signedAction->getAction()) {

            case 'AdminLoginOverride':

                $_SESSION['skipUserActivity'] = true;

                $user = $signedAction->getUser();
                $session->login($user);

                return new \Frootbox\MVC\ResponseRedirect('Dashboard');

            default:
                throw new \Frootbox\Exceptions\InputInvalid('SignedActionUnknown');
        }
    }

    /**
     *
     */
    public function loginAction(
        \Frootbox\MVC\Session $session,
    ): ResponseInterface
    {
        if ($session->isLoggedIn()) {
            return new ResponseRedirect('Dashboard');
        }

        return new Response;
    }

    /**
     *
     */
    public function logoutAction(
        \Frootbox\MVC\Session $session,
    ): ResponseInterface
    {
        $session->logout();

        return new \Frootbox\MVC\ResponseRedirect('Session/login');
    }

    /**
     * @access Public
     */
    public function passwordRequestAction(

    ): ResponseInterface
    {
        return new Response;
    }

    /**
     * @access Public
     */
    public function passwordRequestedAction(

    ): ResponseInterface
    {
        return new Response;
    }

    /**
     * @access Public
     */
    public function passwordSetAction(
        \Frootbox\Http\Get $get,
        \OmniTools\Persistence\Repository\User $userRepository,
    ): ResponseInterface
    {
        // Fetch user
        $user = $userRepository->fetchOne([
            'where' => [
                'email' => $get->get('email'),
            ],
        ]);

        try {
            $user->signedActionVerify('ChangePassword', $get->get('token'));
        }
        catch (\Frootbox\Exceptions\InputInvalid $exception) {
            return new ResponseRedirect($this->getActionUri('actionFailed'));
        }

        return new Response;
    }

    /**
     * @access Public
     */
    public function resendActivationAction(

    ): ResponseInterface
    {
        return new Response([

        ]);
    }
}
