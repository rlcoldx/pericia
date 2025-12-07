<?php

namespace Agencia\Close\Adapters\Twig;

use Agencia\Close\Services\Login\PermissionsService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class VerifyPermission extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('verifyPermission', [$this, 'verifyPermission']),
        ];
    }

    public function verifyPermission(string $permission)
    {
        $permissionService = new PermissionsService();
        return $permissionService->verifyPermissions($permission);
    }
}