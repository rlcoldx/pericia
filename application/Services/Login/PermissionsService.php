<?php 

namespace Agencia\Close\Services\Login;

class PermissionsService {

    public function setPermissionsByDB($permissionsList) {
        $permissionArray = [];
        
        if ($permissionsList && is_array($permissionsList)) {
            foreach($permissionsList as $permission) {
                if (isset($permission['nome'])) {
                    $permissionArray[] = $permission['nome'];
                }
            }
        }
        $this->setPermissions($permissionArray);
    }

    public function setPermissions($permissions) {
        $_SESSION['permissoes'] = json_encode($permissions);
    }

    public function getPermissions() {
        if (isset($_SESSION['permissoes']) && !empty($_SESSION['permissoes'])) {
            return json_decode($_SESSION['permissoes']);
        }
        return [];
    }

    public function verifyPermissions($permissionRequired) {
        // Administrador (tipo 1) tem acesso a tudo
        if (isset($_SESSION['pericia_perfil_tipo']) && $_SESSION['pericia_perfil_tipo'] == 1) {
            return true;
        }
        
        $permissions = $this->getPermissions();
        
        if (!$permissions || !is_array($permissions)) {
            return false;
        }
        
        $found = false;
        foreach($permissions as $permission) {
            if($permissionRequired === $permission) {
                $found = true;
                break;
            }
        }
        return $found;
    }
}

