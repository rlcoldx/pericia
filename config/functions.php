<?php

//WSErro :: Exibe erros lançados :: Front
function EchoMsg($ErrMsg, $ErrNo, $ErrDie = null) {
    $CssClass = ($ErrNo == E_USER_NOTICE ? INFOR : ($ErrNo == E_USER_WARNING ? ALERT : ($ErrNo == E_USER_ERROR ? ERROR : $ErrNo)));

    switch($CssClass):
        case 'accept':
            echo '<div class="alert alert-success alert-dismissable">
    <button class="close" aria-hidden="true" data-dismiss="alert" type="button">&times;</button>
    <h4><i class="icon fa fa-check"></i> Sucesso!</h4>
    '.$ErrMsg.'</div>';
            break;
        case 'infor':
            echo '<div class="alert alert-info alert-dismissable">
    <button class="close" aria-hidden="true" data-dismiss="alert" type="button">&times;</button>
    <h4><i class="icon fa fa-info"></i> Atenção!</h4>
    '.$ErrMsg.'</div>';
            break;
        case 'alert':
            echo '<div class="alert alert-warning alert-dismissable">
    <button class="close" aria-hidden="true" data-dismiss="alert" type="button">&times;</button>
    <h4><i class="icon fa fa-warning"></i> Alerta!</h4>
    '.$ErrMsg.'</div>';
            break;
        case 'error':
            echo '<div class="alert alert-danger alert-dismissable">
    <button class="close" aria-hidden="true" data-dismiss="alert" type="button">&times;</button>
    <h4><i class="icon fa fa-ban"></i> Erro!</h4>
    '.$ErrMsg.'</div>';
            break;
        default:
    endswitch;

    if ($ErrDie):
        die;
    endif;
}

//PHPErro :: personaliza o gatilho do PHP
function PHPErro($ErrNo, $ErrMsg, $ErrFile, $ErrLine) {
    $CssClass = ($ErrNo == E_USER_NOTICE ? INFOR : ($ErrNo == E_USER_WARNING ? ALERT : ($ErrNo == E_USER_ERROR ? ERROR : $ErrNo)));
    echo "<p class=\"trigger {$CssClass}\">";
    echo "<b>Erro na Linha: #{$ErrLine} ::</b> {$ErrMsg}<br>";
    echo "<small>{$ErrFile}</small>";
    echo "<span class=\"ajax_close\"></span></p>";

    if ($ErrNo == E_USER_ERROR):
        die;
    endif;
}

set_error_handler('PHPErro');


function converterDiaSemana($dia) {
	$dias_semana_br = array('Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab');
	$dias_semana_en = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

	$index = array_search($dia, $dias_semana_br);

	if ($index !== false) {
		return $dias_semana_en[$index];
	}
}

// Função de comparação para ordenar por disponibilidade ASC
function compararDisponibilidade($a, $b) {
	return strcmp($a['disponibilidade'], $b['disponibilidade']);
}

function ClearSearch($pesquisa){
	$palavras = array("da","de","di","do","du","para","pra","em","in","por","até","ate");
	return preg_replace('/\b('.implode('|',$palavras).')\b/','',$pesquisa);
}

function traduzirStatusPagamento($status) {
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

    return $statusTraduzido[$status] ?? 'Status desconhecido';
}

function corStatusPagamento($status) {
    $classeBadge = [
        'approved' => 'success',
        'pending' => 'warning',
        'in_process' => 'info',
        'rejected' => 'danger',
        'cancelled' => 'secondary',
        'refunded' => 'primary',
        'in_mediation' => 'info',
        'charged_back' => 'dark'
    ];

    return $classeBadge[$status] ?? 'dark';
}

function getNomeEstado($sigla) {
    $estados = [
        "AC" => "Acre",
        "AL" => "Alagoas",
        "AP" => "Amapá",
        "AM" => "Amazonas",
        "BA" => "Bahia",
        "CE" => "Ceará",
        "DF" => "Distrito Federal",
        "ES" => "Espírito Santo",
        "GO" => "Goiás",
        "MA" => "Maranhão",
        "MT" => "Mato Grosso",
        "MS" => "Mato Grosso do Sul",
        "MG" => "Minas Gerais",
        "PA" => "Pará",
        "PB" => "Paraíba",
        "PR" => "Paraná",
        "PE" => "Pernambuco",
        "PI" => "Piauí",
        "RJ" => "Rio de Janeiro",
        "RN" => "Rio Grande do Norte",
        "RS" => "Rio Grande do Sul",
        "RO" => "Rondônia",
        "RR" => "Roraima",
        "SC" => "Santa Catarina",
        "SP" => "São Paulo",
        "SE" => "Sergipe",
        "TO" => "Tocantins"
    ];

    $sigla = strtoupper($sigla); // Converter a sigla para maiúsculas, caso esteja em minúsculas

    if(array_key_exists($sigla, $estados)) {
        return $estados[$sigla];
    } else {
        return $sigla;
    }
}