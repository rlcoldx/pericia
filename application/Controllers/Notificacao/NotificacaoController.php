<?php

namespace Agencia\Close\Controllers\Notificacao;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Notificacao\NotificacaoModel;
use Agencia\Close\Models\Notificacao\NotificacaoUsuario;

class NotificacaoController extends Controller
{

    public function index($params)
    {
        $this->setParams($params);

        $model = new NotificacaoModel();
        $cidades = $model->getCidades()->getResult();

        $this->render('components/notificacao/criar.twig', [
            'cidades' => $cidades
        ]);
    }

    public function listarUsuario($params)
    {
        $this->setParams($params);

        $usuarioId = $_SESSION['pericia_perfil_id'] ?? null;

        if (!$usuarioId) {
            $this->render('components/notificacao/listar.twig', [
                'notificacoes' => [],
            ]);
            return;
        }

        $model = new NotificacaoUsuario();
        $lista = $model->listarPorUsuario((int) $usuarioId);

        $this->render('components/notificacao/listar.twig', [
            'notificacoes' => $lista->getResult() ?? [],
        ]);
    }

    public function marcarComoLida($params)
    {
        $this->setParams($params);

        $usuarioId = $_SESSION['pericia_perfil_id'] ?? null;
        $id = isset($params['id']) ? (int) $params['id'] : null;

        if (!$usuarioId || !$id) {
            $this->responseJson([
                'success' => false,
                'message' => 'Dados inválidos.'
            ]);
            return;
        }

        $model = new NotificacaoUsuario();
        $result = $model->marcarComoLida($id, (int) $usuarioId);

        if ($result->getResult()) {
            // Contar notificações não lidas restantes
            $lista = $model->listarPorUsuario((int) $usuarioId);
            $notificacoes = $lista->getResult() ?? [];
            $naoLidas = 0;
            foreach ($notificacoes as $n) {
                if (empty($n['lido']) || (int)$n['lido'] === 0) {
                    $naoLidas++;
                }
            }

            $this->responseJson([
                'success' => true,
                'message' => 'Notificação marcada como lida.',
                'nao_lidas' => $naoLidas
            ]);
        } else {
            $this->responseJson([
                'success' => false,
                'message' => 'Erro ao marcar notificação como lida.'
            ]);
        }
    }

    public function contarNaoLidas($params)
    {
        $this->setParams($params);

        header('Content-Type: application/json; charset=utf-8');

        $usuarioId = $_SESSION['pericia_perfil_id'] ?? null;

        if (!$usuarioId) {
            $this->responseJson([
                'success' => false,
                'nao_lidas' => 0
            ]);
            return;
        }

        $model = new NotificacaoUsuario();
        $lista = $model->listarPorUsuario((int) $usuarioId);
        $notificacoes = $lista->getResult() ?? [];
        $naoLidas = 0;
        
        foreach ($notificacoes as $n) {
            if (empty($n['lido']) || (int)$n['lido'] === 0) {
                $naoLidas++;
            }
        }

        $this->responseJson([
            'success' => true,
            'nao_lidas' => $naoLidas
        ]);
    }

    public function enviarNotificacao($params)
    {
        $this->setParams($params);

        $model = new NotificacaoModel();
        $offset = 0;
        $limit = 10000;

        // Garantir que $cidades seja sempre um array
        $cidades = isset($params['cidade']) ? $params['cidade'] : [];
        
        // Se for string, converter para array
        if (is_string($cidades)) {
            $cidades = [$cidades];
        }
        
        // Se for null ou vazio, definir como array vazio
        if (empty($cidades) || $cidades === null) {
            $cidades = [];
        }

        do {
            $usuarios = $model->getUsersID($offset, $limit, $cidades)->getResult();
            if (empty($usuarios)) {
                break;
            }

            $codes = array();
            foreach ($usuarios as $usuario) {
                $codes[] = $usuario['pushKey'];
            }

            if (!empty($codes)) {
                $this->sendNotificacao($codes, $params['titulo'], $params['mensagem']);
            }

            $offset += $limit;
        } while (count($usuarios) === $limit);

        echo json_encode([
            'status' => 'success',
            'message' => 'Notificações enviadas com sucesso!'
        ]);
    }

    public function sendNotificacao($codes, $titulo, $mensagem){

        $data = [
            "app_id" => "79786e40-5d5a-4c3c-84dc-f85de78e05d8",
            "contents" => ["en" => $mensagem],
            "headings" => ["en" => $titulo],
            "include_subscription_ids" => $codes
        ];
        $jsonData = json_encode($data);


        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.onesignal.com/notifications',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Bearer M2MwN2JhZTgtZjIxNi00MDNiLWI2NTEtZmMyNmJkZTYzMWYy'
            ),
        ));

        $response = curl_exec($curl);
        
        if ($response) {
            //echo $response;
        }
        curl_close($curl);
        return $response;

    }

    public function reservaAlerta($params)
    {
        $model = new NotificacaoModel();
        $usuarios = $model->reservaAlertaUsers()->getResult();
        foreach ($usuarios as $usuario) {
            $codes[] = $usuario['pushKey'];
        }
        $response = $this->sendNotificacao($codes, 'Atenção', 'Sua reserva é daqui a 15 minutos. Fique atento ao horário!');
        echo $response;
    }

}