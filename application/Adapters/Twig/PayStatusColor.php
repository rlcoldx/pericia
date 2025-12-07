<?php

namespace Agencia\Close\Adapters\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PayStatusColor extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('payStatusColor', [$this, 'payStatusColor']),
        ];
    }

    public function payStatusColor($status): string
    {
        $classeBadge = [
            'approved' => 'success',
            'pending' => 'warning',
            'in_process' => 'info',
            'rejected' => 'danger',
            'cancelled' => 'danger',
            'refunded' => 'primary',
            'in_mediation' => 'info',
            'charged_back' => 'dark'
        ];
    
        return $classeBadge[$status] ?? 'dark';

    }
}