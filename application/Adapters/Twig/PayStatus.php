<?php

namespace Agencia\Close\Adapters\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PayStatus extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('payStatus', [$this, 'payStatus']),
        ];
    }

    public function payStatus($status): string
    {
        $statusTraduzido = [
            'approved' => 'Aprovado',
            'pending' => 'Pendente',
            'in_process' => 'Em Processo',
            'rejected' => 'Rejeitado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
            'in_mediation' => 'Em Mediação',
            'charged_back' => 'Estornado'
        ];
    
        return $statusTraduzido[$status] ?? 'Não iniciado';

    }
}