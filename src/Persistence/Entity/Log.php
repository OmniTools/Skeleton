<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 */

namespace OmniTools\Persistence\Entity;

class Log extends \OmniTools\Persistence\Entity\AbstractRow
{
    use \OmniTools\Persistence\Entity\Trait\User;

    protected $table = 'logs';
    protected $model = \OmniTools\Persistence\Repository\Log::class;

    /**
     * @param $key
     * @return string
     */
    public static function getType($key): string
    {
        $type = 'String';

        if (str_starts_with($key, 'Date')) {
            $type = 'Date';
        }
        elseif (str_starts_with($key, 'Is')) {
            $type = 'Bool';
        }
        else {
            // p($key);
        }

        return $type;
    }

    /**
     * @return array
     */
    protected function getInsetsForAction(): array
    {
        $payload = $this->getPayload();

        switch ($this->getAction()) {

            case 'CompanyBusinessLicenseUploaded':
            case 'CompanyRegistration':
            case 'CompanyDeleted':
            case 'CompanyRequestProjectDeleted':
                return [ ($payload['company']['title'] ?? 'unbekannt') ];

            case 'CompanyLocationUpdated':
                return [ $payload['company']['title'], $payload['location']['title'] ];

            case 'CompanyProjectDeleted':
            case 'CompanyProjectCreated':
            case 'CompanyProjectRequest':
            case 'CompanyProjectUpdated':
                return [ $payload['company']['title'], $payload['project']['title'] ];

            case 'CompanyProjectTradeAdd':
            case 'CompanyProjectTradeRemove':
            case 'CompanyProjectTradeUpdated':
                return [ $payload['project']['title'], $payload['trade']['title'] ];

            case 'CompanySubscribed':
            case 'CompanySubscriptionCancelled':
            case 'CompanyUnsubscribed':
                return [ ($payload['company']['title'] ?? 'unbekannt'), $payload['subscription']['plan'] ];

            case 'UserRegistration':
            case 'UserDeleted':
            case 'UserUpdated':
                return [ $payload['user']['email'] ];

            case 'CompanyEmployeeAvailabilityCreate':
                return [];


            default:
                return [];
        }
    }

    /**
     *
     */
    public function getActionParsed(\Frootbox\MVC\Translator $translator): string
    {
        $logString = $translator->translate('Logs' . $this->getAction());

        $logString = str_replace('%s', '<code>%s</code>', $logString);
        $logString = preg_replace('#(%[0-9]+\$s)#', '<code>\\1</code>', $logString);
        $logInsets = $this->getInsetsForAction();

        if (!empty($logInsets)) {
            $logString = vsprintf($logString, $logInsets);
        }

        return $logString;
    }

    /**
     * @return array
     */
    public function getChanges(): array
    {
        $payload = $this->getPayload();

        if (!isset($payload['changes'])) {
            return [];
        }

        $changes = $payload['changes'];

        foreach ($changes as $index => $change) {

            $changes[$index]['type'] = self::getType($change['key']);

            if ($changes[$index]['type'] == 'Bool') {
                $changes[$index]['before'] = !empty($changes[$index]['before']);
                $changes[$index]['after'] = !empty($changes[$index]['after']);
            }

        }

        return $changes;
    }

    /**
     * @return Company|null
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getCompany(): ?\HandwerkConnected\Persistence\Entity\Company
    {
        if (empty($this->getForCompanyId())) {
            return null;
        }

        try {
            // Fetch company
            $companyRepository = $this->getDb()->getRepository(\HandwerkConnected\Persistence\Repository\Company::class);
            $company = $companyRepository->fetchById($this->getForCompanyId());

            return $company;
        }
        catch (\Frootbox\Exceptions\NotFound $e) {
            return null;
        }
    }

    /**
     * @return \HandwerkConnected\Persistence\Entity\User|null
     */
    public function getForUser(): ?\HandwerkConnected\Persistence\Entity\User
    {
        if (empty($this->getForUserId())) {
            return null;
        }

        // Fetch user
        $userRepository = $this->getDb()->getRepository(\HandwerkConnected\Persistence\Repository\User::class);

        return $userRepository->fetchById($this->getForUserId());
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return !empty(parent::getPayload()) ? json_decode(parent::getPayload(), true) : [];
    }
}
