<?php

namespace Agencia\Close\Adapters\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HourCheck extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('HourCheck', [$this, 'HourCheck']),
        ];
    }

    public function hourCheck($hora,$id_user): string
    {
       
        $hora_atual = date('Y-m-d H:i:s', strtotime("+1 days"));
        $dada_amanha = date('Y-m-d', strtotime("+1 days"));

        if( strtotime($hora_atual) >=  strtotime($dada_amanha.' '.$horas[$hora]) ){
            $disabled_class = 'disabled';
            $disabled = 'disabled="disabled"';
            $disabled_nome = 'Indisponível';
        }else{
            $disabled_class = '';
            $disabled = '';
            $disabled_nome = 'Disponível';
        }

        $dada_check = $dada_amanha = date('d-m-Y', strtotime("+1 days"));
        $disabled_class_selected = '';
        
        $sql_horario = "SELECT * FROM ".$table_prefix."pagamentos WHERE vendedor = '".$consultor[0]->ID."' AND dia_agendamento = '".$dada_check."' AND horario_agendamento = '".$horas[$hora]."' AND `status` in ('1','2','3','4','5')";
        $dados_horario = $wpdb->get_results($sql_horario);
        if($dados_horario){
            $disabled_class = 'disabled';
            $disabled = 'disabled="disabled"';
            $disabled_nome = 'Ocupado';
            $disabled_class_selected = 'danger';
        }

        return $return;
    }
}