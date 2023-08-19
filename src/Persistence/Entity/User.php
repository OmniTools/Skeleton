<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 */

namespace OmniTools\Persistence\Entity;

class User extends \OmniTools\Persistence\Entity\AbstractRow implements \Frootbox\MVC\Persistence\Entities\Interfaces\UserInterface
{
    use \OmniTools\Persistence\Entity\Trait\Config;
    use \OmniTools\Persistence\Entity\Trait\Uid;
    use \OmniTools\Persistence\Entity\Trait\User;

    protected $table = 'users';
    protected $model = \HandwerkConnected\Persistence\Repository\User::class;

    /**
     * Check if user can gain admin access
     *
     * @return bool
     */
    public function canGainAdminAccess(): bool
    {
        return !empty($_SESSION['adminId']);
    }

    /**
     *
     */
    public function delete()
    {
        // Cleanup market connections
        $sql = 'DELETE FROM users_2_markets WHERE userId = ' . $this->getId();
        $this->getDb()->query($sql);

        // Cleanup thumbnail
        $thumbnail = $this->getThumbnail();

        if ($thumbnail) {
            $thumbnail->delete();
        }

        parent::delete();
    }

    /**
     *
     */
    public function getConsent(string $type): \HandwerkConnected\Persistence\Entity\User\Consent
    {
        // Fetch consent
        $consentRepository = $this->getDb()->getRepository(\HandwerkConnected\Persistence\Repository\User\Consent::class);
        $consent = $consentRepository->fetchOne([
            'where' => [
                'userId' => $this->getId(),
                'type' => $type,
            ],
            'order' => [ 'date DESC' ],
        ]);

        if (empty($consent)) {

            $consent = new \HandwerkConnected\Persistence\Entity\User\Consent([

            ]);
        }

        return $consent;
    }

    /**
     *
     */
    public function getBaseRole(): ?string
    {
        return $this->data['role'];
    }

    /**
     *
     */
    public function getContactPerson(): ?self
    {
        if (empty($this->getContactPersonId())) {
            return null;
        }

        return $this->getModel()->fetchById($this->getContactPersonId());
    }

    /**
     *
     */
    public function getInitials(): string
    {
        if (!empty($this->getFirstname()) and !empty($this->getLastname())) {
            return $this->getFirstname()[0] . $this->getLastname()[0];
        }
        elseif (!empty($this->getLastname())) {
            return strtoupper(substr($this->getLastname(), 0, 2));
        }
        else {
            return strtoupper(substr($this->getEmail(), 0, 2));
        }
    }

    /**
     *
     */
    public function getName(bool $fallback = false): ?string
    {
        $name = trim($this->getFirstname() . ' ' . $this->getLastname());

        if ($fallback and empty($name)) {
            $name = $this->getEmail();
        }

        return $name;
    }

    /**
     *
     */
    public function getRole(): ?\Docsimple\Persistence\Entities\Role
    {
        if (empty($this->getRoleId())) {
            return null;
        }

        // Fetch role
        $roleRepository = $this->getDb()->getRepository(\Docsimple\Persistence\Repositories\Role::class);
        $role = $roleRepository->fetchById($this->getRoleId());

        return $role;
    }

    /**
     *
     */
    public function getThumbnail(): ?\OmniTools\Persistence\Entity\File
    {
        // Fetch file
        $fileRepository = $this->getDb()->getRepository(\OmniTools\Persistence\Repository\File::class);
        $file = $fileRepository->fetchOne([
            'where' => [
                'fid' => $this->getUid('thumbnail'),
            ],
        ]);

        return $file;
    }

    /**
     * Generate users admin url
     *
     * @return string
     */
    public function getUriAdmin(string $action = 'details', array $payload = []): string
    {
        $payload['userId'] = $this->getId();

        return SERVER_PATH . 'Admin/Users/' . $action . '?' . http_build_query($payload);
    }

    /**
     *
     */
    public function isSubscribed(string $type, int $itemId = null): bool
    {
        $subscriptionRepository = $this->getDb()->getRepository(\HandwerkConnected\Persistence\Repository\User\Subscription::class);
        $subscription = $subscriptionRepository->fetchOne([
            'where' => [
                'userId' => $this->getId(),
                'type' => $type,
                'itemId' => $itemId,
            ],
        ]);

        return !empty($subscription);
    }

    /**
     * @param Job $job
     * @return Job\Report
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function jobAccept(\OmniTools\Persistence\Entity\Job  $job): \OmniTools\Persistence\Entity\Report
    {
        // Check if job is already accepted
        $reportRepository = $this->getDb()->getRepository(\OmniTools\Persistence\Repository\Report::class);
        $check = $reportRepository->fetchOne([
            'where' => [
                'userId' => $this->getId(),
                'jobId' => $job->getId(),
            ],
        ]);

        if (!empty($check)) {
            return $check;
        }

        // Compose new report
        $report = new \OmniTools\Persistence\Entity\Report([
            'userId' => $this->getId(),
            'jobId' => $job->getId(),
            'state' => 'Accepted',
            'dateAccepted' => date('Y-m-d H:i:s'),
        ]);

        // Insert new report
        $report = $reportRepository->persist($report);

        return $report;
    }

    /**
     * Set users password
     *
     * @param string $password
     * @return void
     */
    public function setPassword(string $password): void
    {
        $this->data['password'] = password_hash($password, PASSWORD_DEFAULT);
        $this->changed['password'] = true;
    }

    /**
     * @param string $actionTitle
     * @param array|null $parameters
     * @return User\SignedAction
     */
    public function signedActionCreate(string $actionTitle, array $parameters = null ): \OmniTools\Persistence\Entity\User\SignedAction
    {
        // Obtain repository
        $signedActionRepository = $this->getDb()->getRepository(\OmniTools\Persistence\Repository\User\SignedAction::class);

        if (!empty($parameters['preventDuplicates'])) {

            // Cleanup existing actions
            $result = $signedActionRepository->fetch([
                'where' => [
                    'userId' => $this->getId(),
                    'action' => $actionTitle,
                ],
            ]);

            $result->map('delete');
        }

        $signedAction = new \OmniTools\Persistence\Entity\User\SignedAction([
            'userId' => $this->getId(),
            'action' => $actionTitle,
            'token' => md5(microtime(true)),
        ]);

        $signedAction = $signedActionRepository->persist($signedAction);

        return $signedAction;
    }

    /**
     * @param string $action
     * @param string $token
     * @return void
     * @throws \Exception
     */
    public function signedActionVerify(string $action, string $token): void
    {
        $signedActionRepository = $this->getDb()->getRepository(\OmniTools\Persistence\Repository\User\SignedAction::class);

        $signedAction = $signedActionRepository->fetchOne([
            'where' => [
                'userId' => $this->getId(),
                'action' => $action,
                'token' => $token,
            ],
        ]);

        if (empty($signedAction)) {
            throw new \Frootbox\Exceptions\InputInvalid('Token not found.');
        }

        $date = new \DateTime($signedAction->getDate());
        $now = new \DateTime('now');

        $hours = ($now->getTimestamp() - $date->getTimestamp()) / 60 / 60;

        if ($hours >= 24) {

            $signedAction->delete();

            throw new \Frootbox\Exceptions\InputInvalid('Token expired.');
        }
    }
}
