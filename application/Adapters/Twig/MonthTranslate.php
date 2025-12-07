<?php

namespace Agencia\Close\Adapters\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MonthTranslate extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('monthTranslate', [$this, 'monthTranslate']),
        ];
    }

    public function monthTranslate($month): string
    {
        switch ($month) {
            case 'January': $month = 'Janeiro'; break;
            case 'February': $month = 'Fevereiro'; break;
            case 'March': $month = 'Março'; break;
            case 'April': $month = 'Abril'; break;
            case 'May': $month = 'Maio'; break;
            case 'June': $month = 'Junho'; break;
            case 'July': $month = 'Julho'; break;
            case 'August': $month = 'Agosto'; break;
            case 'September': $month = 'Setembro'; break;
            case 'October': $month = 'Outubro'; break;
            case 'November': $month = 'Novembro'; break;
            case 'December': $month = 'Dezembro'; break;
        }
        return $month;
    }
}