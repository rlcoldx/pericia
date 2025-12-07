<?php

namespace Agencia\Close\Adapters\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UserPhone extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('UserPhone', [$this, 'formatPhone']),
        ];
    }

    public function formatPhone($phone): string
    {
        if($phone != '') {
            $phone = preg_replace("/[^0-9]/", "", $phone);
            if (strlen($phone) === 10) {
                $phone = preg_replace("/(\d{2})(\d{4})(\d{4})/", "(\$1) \$2-\$3", $phone);
            } elseif (strlen($phone) === 11) {
                $phone = preg_replace("/(\d{2})(\d{5})(\d{4})/", "(\$1) \$2-\$3", $phone);
            }
            return $phone;
        }else{
            return '';
        }
    }
}