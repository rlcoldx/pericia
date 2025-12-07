<?php

namespace Agencia\Close\Models\Notificacao;

use Agencia\Close\Conn\Read;
use Agencia\Close\Models\Model;

class NotificacaoModel extends Model 
{

    public function getCidades(): Read
    {
        $read = new Read();
        $read->FullRead("SELECT cidade FROM usuarios WHERE cidade is not null AND cidade <> '' GROUP BY cidade");
        return $read;
    }

    public function getUsersID($offset = 0, $limit = 10000, $cidades = [])
    {
        $read = new Read();
        
        // Garantir que $cidades seja sempre um array
        if (!is_array($cidades)) {
            $cidades = [$cidades];
        }
        
        // Filtrar valores vazios ou nulos
        $cidades = array_filter($cidades, function($cidade) {
            return !empty($cidade) && $cidade !== null && $cidade !== '';
        });
        
        // Construir a query base
        $termos = "WHERE tipo = 4 AND pushKey is not null AND pushKey <> 'null'";
        
        // Adicionar filtro de cidade se houver cidades válidas
        if (!empty($cidades)) {
            // Escapar e preparar as cidades para a query
            $cidadesEscapadas = array_map(function($cidade) {
                return "'" . addslashes($cidade) . "'";
            }, $cidades);
            
            $termos .= " AND cidade IN (" . implode(',', $cidadesEscapadas) . ")";
        }
        
        $termos .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        
        $parseString = "limit={$limit}&offset={$offset}";
        $read->exeRead("usuarios", $termos, $parseString);
        return $read;
    }

    public function reservaAlertaUsers(): Read
    {
        $read = new Read();
        $read->FullRead("
            SELECT u.*
            FROM reservas r
            INNER JOIN pagamentos p ON p.id_reserva = r.id
            INNER JOIN usuarios u ON u.id = r.id_usuario
            WHERE r.status_reserva = 'Aceito'
              AND p.pagamento_status = 'approved'
              AND CONCAT(r.data_reserva, ' ', r.hora_reserva) = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 15 MINUTE), '%Y-%m-%d %H:%i')
              AND u.pushKey is not null
              AND u.pushKey <> 'null'
        ");
        return $read;
    }
}