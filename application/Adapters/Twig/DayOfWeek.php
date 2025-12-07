<?php

namespace Agencia\Close\Adapters\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use DateTime;

class DayOfWeek extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('dayOfWeek', [$this, 'getDayOfWeek']),
        ];
    }

    public function getDayOfWeek($date): string
    {
        $dateTime = new DateTime($date);
        $daysOfWeek = [
            'Sunday' => 'Domingo',
            'Monday' => 'Segunda-feira',
            'Tuesday' => 'Terça-feira',
            'Wednesday' => 'Quarta-feira',
            'Thursday' => 'Quinta-feira',
            'Friday' => 'Sexta-feira',
            'Saturday' => 'Sábado'
        ];

        $day = $dateTime->format('l'); // Retorna o dia da semana em inglês completo

        return $daysOfWeek[$day] ?? 'Dia inválido';
    }
}
