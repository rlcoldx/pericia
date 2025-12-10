<?php

namespace Agencia\Close\Controllers\Parecer;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Parecer\Parecer;
use Agencia\Close\Models\Reclamada\Reclamada;
use Agencia\Close\Models\Reclamante\Reclamante;
use Agencia\Close\Models\Assistente\Assistente;
use Agencia\Close\Services\Notificacao\EmailNotificationService;
use Agencia\Close\Helpers\DataTableResponse;
use Agencia\Close\Models\Equipe\Equipe;
use Agencia\Close\Models\Tarefa\Tarefa;

class ParecerController extends Controller
{
    public function index($params)
    {
        $this->setParams($params);
        $this->requirePermission('parecer_ver');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $reclamadaModel = new Reclamada();
        $reclamanteModel = new Reclamante();
        $parecerModel = new Parecer();

        $this->render('pages/parecer/index.twig', [
            'titulo' => 'Pareceres',
            'page' => 'pareceres',
            'reclamadas' => $reclamadaModel->listar((int) $empresa)->getResult() ?? [],
            'reclamantes' => $reclamanteModel->listar((int) $empresa)->getResult() ?? [],
            'tipos' => $parecerModel->listarTipos((int) $empresa)->getResult() ?? [],
        ]);
    }

    public function criar($params)
    {
        $this->setParams($params);
        $this->requirePermission('parecer_cadastrar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $reclamadaModel = new Reclamada();
        $reclamanteModel = new Reclamante();
        $parecerModel = new Parecer();
        $assistenteModel = new Assistente();
        $equipeModel = new Equipe();

        $this->render('pages/parecer/form.twig', [
            'titulo' => 'Novo Parecer',
            'page' => 'pareceres',
            'action' => 'criar',
            'parecer' => null,
            'reclamadas' => $reclamadaModel->listar((int) $empresa)->getResult() ?? [],
            'reclamantes' => $reclamanteModel->listar((int) $empresa)->getResult() ?? [],
            'assistentes' => $assistenteModel->listar((int) $empresa)->getResult() ?? [],
            'tipos' => $parecerModel->listarTipos((int) $empresa)->getResult() ?? [],
            'usuarios' => $equipeModel->getUsuariosAtivos((int) $empresa)->getResult() ?? [],
            'tarefa' => null,
        ]);
    }

    public function salvarCriar($params)
    {
        // Definir header JSON no início para evitar corrupção
        header('Content-Type: application/json; charset=utf-8');
        
        $this->setParams($params);
        $this->requirePermission('parecer_cadastrar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            // Limpar qualquer output buffer antes de enviar JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }
            $this->responseJson(['success' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        $dataRealizacao = $_POST['data_realizacao'] ?? '';
        $tipo = $_POST['tipo'] ?? '';

        if (empty($dataRealizacao) || empty($tipo)) {
            $this->responseJson(['success' => false, 'message' => 'Data da Realização e Tipo são obrigatórios.']);
            return;
        }

        // Força o tipo em letras maiúsculas
        if (!empty($tipo)) {
            $tipo = mb_strtoupper($tipo, 'UTF-8');
        }

        // Verifica se o tipo existe, se não, cria
        $parecerModel = new Parecer();
        $tiposExistentes = $parecerModel->listarTipos((int) $empresa)->getResult() ?? [];
        $tiposNomes = array_column($tiposExistentes, 'nome');
        
        if (!in_array($tipo, $tiposNomes, true)) {
            $parecerModel->criarTipo((int) $empresa, $tipo);
        }

        $dados = [
            'empresa' => (int) $empresa,
            'data_realizacao' => $dataRealizacao,
            'data_fatal' => $_POST['data_fatal'] !== '' ? $_POST['data_fatal'] : null,
            'data_entrega_parecer' => $_POST['data_entrega_parecer'] !== '' ? $_POST['data_entrega_parecer'] : null,
            'status_parecer' => $_POST['status_parecer'] !== '' ? $_POST['status_parecer'] : null,
            'tipo' => $tipo,
            'assistente' => $_POST['assistente'] !== '' ? $_POST['assistente'] : null,
            'assistente_id' => !empty($_POST['assistente_id']) ? (int) $_POST['assistente_id'] : null,
            'reclamada_id' => !empty($_POST['reclamada_id']) ? (int) $_POST['reclamada_id'] : null,
            'reclamante_id' => !empty($_POST['reclamante_id']) ? (int) $_POST['reclamante_id'] : null,
            'funcoes' => $_POST['funcoes'] !== '' ? $_POST['funcoes'] : null,
            'observacoes' => $_POST['observacoes'] !== '' ? $_POST['observacoes'] : null,
        ];

        $result = $parecerModel->criar($dados);

        if (!$result->getResult()) {
            // Limpar qualquer output buffer antes de enviar JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }
            $this->responseJson(['success' => false, 'message' => 'Erro ao cadastrar.']);
            return;
        }

        $idParecer = (int) $result->getResult();

        // Salvar tarefa se fornecida (não bloqueia o cadastro se falhar)
        try {
            $temDadosTarefa = isset($_POST['tarefa_concluido']) || !empty($_POST['tarefa_usuario_responsavel_id']) || !empty($_POST['tarefa_data_conclusao']);
            
            if ($temDadosTarefa) {
                $tarefaModel = new Tarefa();
                $tarefaModel->salvarTarefa('parecer', $idParecer, (int) $empresa, [
                    'concluido' => isset($_POST['tarefa_concluido']) && $_POST['tarefa_concluido'] == '1',
                    'usuario_responsavel_id' => $_POST['tarefa_usuario_responsavel_id'] ?? null,
                    'data_conclusao' => $_POST['tarefa_data_conclusao'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            // Não bloqueia o cadastro se a tarefa falhar
        } catch (\Error $e) {
            // Não bloqueia o cadastro se a tarefa falhar
        }

        try {
            $this->enviarNotificacaoEmailParecer($empresa, $idParecer, $dados, 'criar');
        } catch (\Exception $e) {
            // Erro silencioso no envio de email
        }

        // Limpar qualquer output buffer antes de enviar JSON
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        $this->responseJson(['success' => true, 'message' => 'Parecer cadastrado com sucesso.']);
    }

    public function editar($params)
    {
        $this->setParams($params);
        $this->requirePermission('parecer_gerenciar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($params['id']) ? (int) $params['id'] : null;

        if (!$empresa || !$id) {
            $this->redirectUrl(DOMAIN . '/pareceres');
            return;
        }

        $parecerModel = new Parecer();
        $parecer = $parecerModel->getPorId($id, (int) $empresa);

        if (!$parecer->getResult()) {
            $this->redirectUrl(DOMAIN . '/pareceres');
            return;
        }

        $reclamadaModel = new Reclamada();
        $reclamanteModel = new Reclamante();
        $assistenteModel = new Assistente();
        $equipeModel = new Equipe();
        $tarefaModel = new Tarefa();

        // Buscar tarefa existente
        $tarefaRead = $tarefaModel->getPorModuloRegistro('parecer', $id, (int) $empresa);
        $tarefa = $tarefaRead->getResult()[0] ?? null;

        $this->render('pages/parecer/form.twig', [
            'titulo' => 'Editar Parecer',
            'page' => 'pareceres',
            'action' => 'editar',
            'parecer' => $parecer->getResult()[0],
            'reclamadas' => $reclamadaModel->listar((int) $empresa)->getResult() ?? [],
            'reclamantes' => $reclamanteModel->listar((int) $empresa)->getResult() ?? [],
            'assistentes' => $assistenteModel->listar((int) $empresa)->getResult() ?? [],
            'tipos' => $parecerModel->listarTipos((int) $empresa)->getResult() ?? [],
            'usuarios' => $equipeModel->getUsuariosAtivos((int) $empresa)->getResult() ?? [],
            'tarefa' => $tarefa,
        ]);
    }

    public function salvarEditar($params)
    {
        // Definir header JSON no início para evitar corrupção
        header('Content-Type: application/json; charset=utf-8');
        
        $this->setParams($params);
        $this->requirePermission('parecer_gerenciar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;

        if (!$empresa || !$id) {
            // Limpar qualquer output buffer antes de enviar JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $dataRealizacao = $_POST['data_realizacao'] ?? '';
        $tipo = $_POST['tipo'] ?? '';

        if (empty($dataRealizacao) || empty($tipo)) {
            // Limpar qualquer output buffer antes de enviar JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }
            $this->responseJson(['success' => false, 'message' => 'Data da Realização e Tipo são obrigatórios.']);
            return;
        }

        // Força o tipo em letras maiúsculas
        if (!empty($tipo)) {
            $tipo = mb_strtoupper($tipo, 'UTF-8');
        }

        // Verifica se o tipo existe, se não, cria
        $parecerModel = new Parecer();
        $tiposExistentes = $parecerModel->listarTipos((int) $empresa)->getResult() ?? [];
        $tiposNomes = array_column($tiposExistentes, 'nome');
        
        if (!in_array($tipo, $tiposNomes, true)) {
            $parecerModel->criarTipo((int) $empresa, $tipo);
        }

        $dados = [
            'data_realizacao' => $dataRealizacao,
            'data_fatal' => $_POST['data_fatal'] !== '' ? $_POST['data_fatal'] : null,
            'data_entrega_parecer' => $_POST['data_entrega_parecer'] !== '' ? $_POST['data_entrega_parecer'] : null,
            'status_parecer' => $_POST['status_parecer'] !== '' ? $_POST['status_parecer'] : null,
            'tipo' => $tipo,
            'assistente' => $_POST['assistente'] !== '' ? $_POST['assistente'] : null,
            'assistente_id' => !empty($_POST['assistente_id']) ? (int) $_POST['assistente_id'] : null,
            'reclamada_id' => !empty($_POST['reclamada_id']) ? (int) $_POST['reclamada_id'] : null,
            'reclamante_id' => !empty($_POST['reclamante_id']) ? (int) $_POST['reclamante_id'] : null,
            'funcoes' => $_POST['funcoes'] !== '' ? $_POST['funcoes'] : null,
            'observacoes' => $_POST['observacoes'] !== '' ? $_POST['observacoes'] : null,
        ];

        $result = $parecerModel->atualizar($id, (int) $empresa, $dados);

        if (!$result->getResult()) {
            // Limpar qualquer output buffer antes de enviar JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }
            $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar.']);
            return;
        }

        // Salvar tarefa se fornecida (não bloqueia a atualização se falhar)
        try {
            $temDadosTarefa = isset($_POST['tarefa_concluido']) || !empty($_POST['tarefa_usuario_responsavel_id']) || !empty($_POST['tarefa_data_conclusao']);
            
            if ($temDadosTarefa) {
                $tarefaModel = new Tarefa();
                $tarefaModel->salvarTarefa('parecer', $id, (int) $empresa, [
                    'concluido' => isset($_POST['tarefa_concluido']) && $_POST['tarefa_concluido'] == '1',
                    'usuario_responsavel_id' => $_POST['tarefa_usuario_responsavel_id'] ?? null,
                    'data_conclusao' => $_POST['tarefa_data_conclusao'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            // Não bloqueia a atualização se a tarefa falhar
        } catch (\Error $e) {
            // Não bloqueia a atualização se a tarefa falhar
        }

        try {
            $this->enviarNotificacaoEmailParecer($empresa, $id, $dados, 'editar');
        } catch (\Exception $e) {
            // Erro silencioso no envio de email
        }

        // Limpar qualquer output buffer antes de enviar JSON
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        $this->responseJson(['success' => true, 'message' => 'Parecer atualizado com sucesso.']);
    }

    private function enviarNotificacaoEmailParecer(int $empresa, int $idParecer, array $dados, string $acao): void
    {
        try {
            $reclamadaModel = new Reclamada();
            $reclamanteModel = new Reclamante();
            
            $reclamadaNome = 'N/A';
            $reclamanteNome = 'N/A';
            
            if (!empty($dados['reclamada_id'])) {
                $reclamadaRead = $reclamadaModel->getPorId($dados['reclamada_id'], $empresa);
                $reclamadaNome = $reclamadaRead->getResult()[0]['nome'] ?? 'N/A';
            }
            
            if (!empty($dados['reclamante_id'])) {
                $reclamanteRead = $reclamanteModel->getPorId($dados['reclamante_id'], $empresa);
                $reclamanteNome = $reclamanteRead->getResult()[0]['nome'] ?? 'N/A';
            }

            $dadosEmail = [
                'titulo' => $acao === 'criar' ? 'Novo Parecer Cadastrado' : 'Parecer Atualizado',
                'modulo' => 'Parecer',
                'acao' => $acao === 'criar' ? 'Criado' : 'Editado',
                'detalhes' => sprintf(
                    '<p><strong>Data Realização:</strong> %s</p><p><strong>Data Fatal:</strong> %s</p><p><strong>Tipo:</strong> %s</p><p><strong>Assistente:</strong> %s</p><p><strong>Reclamada:</strong> %s</p><p><strong>Reclamante:</strong> %s</p>',
                    date('d/m/Y', strtotime($dados['data_realizacao'])),
                    $dados['data_fatal'] ? date('d/m/Y', strtotime($dados['data_fatal'])) : 'N/A',
                    htmlspecialchars($dados['tipo'] ?? 'N/A'),
                    htmlspecialchars($dados['assistente'] ?? 'N/A'),
                    htmlspecialchars($reclamadaNome),
                    htmlspecialchars($reclamanteNome)
                ),
                'mensagem' => sprintf(
                    'Parecer do tipo %s foi %s.',
                    htmlspecialchars($dados['tipo'] ?? ''),
                    $acao === 'criar' ? 'cadastrado' : 'atualizado'
                ),
                'url' => DOMAIN . '/pareceres/editar/' . $idParecer
            ];

            $emailService = new EmailNotificationService();
            $tipo = $acao === 'criar' ? 'parecer_criar' : 'parecer_editar';
            $permissoes = ['parecer_ver', 'parecer_gerenciar'];
            
            $emailService->criarNotificacaoEEmail($tipo, 'parecer', $acao, $permissoes, $dadosEmail, $empresa, $idParecer, true);
        } catch (\Exception $e) {
            error_log('Erro ao enviar notificação por e-mail: ' . $e->getMessage());
        }
    }

    public function datatable($params)
    {
        $this->setParams($params);
        $this->requirePermission('parecer_ver');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->responseJson([
                'draw' => (int) ($_GET['draw'] ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
            return;
        }

        $dtParams = DataTableResponse::getParams();

        $filtros = [];
        if (!empty($_GET['data_inicio'])) {
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }
        if (!empty($_GET['data_fim'])) {
            $filtros['data_fim'] = $_GET['data_fim'];
        }
        if (!empty($_GET['tipo'])) {
            $filtros['tipo'] = $_GET['tipo'];
        }
        if (!empty($_GET['reclamada_id'])) {
            $filtros['reclamada_id'] = $_GET['reclamada_id'];
        }
        if (!empty($_GET['reclamante_id'])) {
            $filtros['reclamante_id'] = $_GET['reclamante_id'];
        }

        $parecerModel = new Parecer();
        $result = $parecerModel->getPareceresDataTable((int) $empresa, $dtParams, $filtros);

        $formattedData = [];
        foreach ($result['data'] as $p) {
            $formattedData[] = [
                date('d/m/Y', strtotime($p['data_realizacao'])),
                $p['data_fatal'] ? date('d/m/Y', strtotime($p['data_fatal'])) : '-',
                htmlspecialchars($p['tipo'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($p['assistente'] ?? '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($p['reclamada_nome'] ?? '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($p['reclamante_nome'] ?? '-', ENT_QUOTES, 'UTF-8'),
                $this->formatAcoesCell($p['id'] ?? null),
            ];
        }

        $response = DataTableResponse::format(
            $formattedData,
            (int) $result['total'],
            (int) $result['filtered'],
            (int) $dtParams['draw']
        );

        $this->responseJson($response);
    }

    private function formatAcoesCell(?int $id): string
    {
        if (!$id) {
            return '';
        }

        $html = '<div class="d-flex">';
        $html .= '<a href="' . DOMAIN . '/pareceres/editar/' . $id . '" ';
        $html .= 'class="btn btn-success shadow btn-xs sharp me-1" ';
        $html .= 'data-bs-toggle="tooltip" data-bs-title="Editar">';
        $html .= '<i class="fa fa-pencil"></i></a>';
        $html .= '</div>';
        return $html;
    }
}
