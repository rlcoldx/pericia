<?php

namespace Agencia\Close\Adapters\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UserCPF extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('UserCPF', [$this, 'UserCPF']),
        ];
    }

    public function UserCPF($cpf): string
    {
        if($cpf != '') {
            $cpf = preg_replace("/[^0-9]/", "", $cpf);
            $cpf = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cpf);
            return $cpf;
        }else{
            return '';
        }
    }
}