<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 */

namespace OmniTools\Persistence\Entity\User;

class SignedAction extends \OmniTools\Persistence\Entity\AbstractRow
{
    use \OmniTools\Persistence\Entity\Trait\User;

    protected $table = 'users_signedactions';
    protected $model = \OmniTools\Persistence\Repository\User\SignedAction::class;

    /**
     *
     */
    public function getUri(): string
    {
        return SERVER_PATH . 'Session/captureSignedAction?uid=' . $this->getUserId() . '&action=' . $this->getAction() . '&token=' . $this->getToken();
    }
}
