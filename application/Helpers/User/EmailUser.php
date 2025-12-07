<?php

namespace Agencia\Close\Helpers\User;

use Agencia\Close\Models\User\User;

class EmailUser
{
    public static function verifyIfEmailExist(Identification $identification): bool
    {
        if ($identification->getType() === 'email') {
            $userEmail = new User();
            $result = $userEmail->emailExist($identification->getIdentification());
            if($result->getResult()){
                return true;
            }
        }
        return false;
    }
}