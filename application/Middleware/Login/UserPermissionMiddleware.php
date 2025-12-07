<?php

namespace Agencia\Close\Middleware\Login;

use Agencia\Close\Middleware\Middleware;
use Agencia\Close\Models\User\Permissions as PermissionsModel;
use Agencia\Close\Services\Login\LoginSession;
use Agencia\Close\Services\Login\PermissionsService;

class UserPermissionMiddleware extends Middleware
{
    public function run()
    {
        $loginSession = new LoginSession();
        if($loginSession->userIsLogged()) {
            $userId = $loginSession->getUserId();
            $permissions = new PermissionsModel();
            $permissionsList = $permissions->getPermissions($userId)->getResult();
    
            $permissions = new PermissionsService();
            $permissions->setPermissionsByDB($permissionsList);
        }
    }
}