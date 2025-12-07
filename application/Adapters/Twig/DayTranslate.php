<?php

namespace Agencia\Close\Adapters\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DayTranslate extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('dayTranslate', [$this, 'dayTranslate']),
        ];
    }

    public function dayTranslate($day, $invert = false): string
    {
        if($invert == false){
            switch ($day) {
                case 'Mon': $day = 'Segunda'; break;
                case 'Tue': $day = 'Terça'; break;
                case 'Wed': $day = 'Quarta'; break;
                case 'Thu': $day = 'Quinta'; break;
                case 'Fri': $day = 'Sexta'; break;
                case 'Sat': $day = 'Sábado'; break;
                case 'Sun': $day = 'Domingo'; break;
                default;
            }
        }else{
            switch ($day) {
                case 'Segunda': $day = 'Mon'; break;
                case 'Terça': $day = 'Tue'; break;
                case 'Quarta': $day = 'Wed'; break;
                case 'Quinta': $day = 'Thu'; break;
                case 'Sexta': $day = 'Fri'; break;
                case 'Sábado': $day = 'Sat'; break;
                case 'Domingo': $day = 'Sun'; break;
            }
        }

        return $day;
    }
}