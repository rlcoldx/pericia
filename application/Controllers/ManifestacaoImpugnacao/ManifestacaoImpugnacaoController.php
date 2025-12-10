<?php

namespace Agencia\Close\Controllers\ManifestacaoImpugnacao;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\ManifestacaoImpugnacao\ManifestacaoImpugnacao;
use Agencia\Close\Models\Reclamada\Reclamada;
use Agencia\Close\Models\Reclamante\Reclamante;
use Agencia\Close\Models\Perito\Perito;
use Agencia\Close\Services\Notificacao\EmailNotificationService;
use Agencia\Close\Helpers\DataTableResponse;
use Agencia\Close\Models\Equipe\Equipe;
use Agencia\Close\Models\Tarefa\Tarefa;

class ManifestacaoImpugnacaoController extends Controller
{
    public function index($params)
    {
        $this->setParams($params);
        $this->requirePermission('manifestacao_impugnacao_ver');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $reclamadaModel = new Reclamada();
        $reclamanteModel = new Reclamante();
        $peritoModel = new Perito();

        $this->render('pages/manifestacao_impugnacao/index.twig', [
            'titulo' => 'Manifestações e Impugnações',
            'page' => 'manifestacoes_impugnacoes',
            'reclamadas' => $reclamadaModel->listar((int) $empresa)->getResult() ?? [],
            'reclamantes' => $reclamanteModel->listar((int) $empresa)->getResult() ?? [],
            'peritos' => $peritoModel->getPeritosAtivos((int) $empresa)->getResult() ?? [],
        ]);
    }

    public function criar($params)
    {
        $this->setParams($params);
        $this->requirePermission('manifestacao_impugnacao_criar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $reclamadaModel = new Reclamada();
        $reclamanteModel = new Reclamante();
        $peritoModel = new Perito();
        $manifestacaoModel = new ManifestacaoImpugnacao();
        $equipeModel = new Equipe();

        $this->render('pages/manifestacao_impugnacao/form.twig', [
            'titulo' => 'Nova Manifestação/Impugnação',
            'page' => 'manifestacoes_impugnacoes',
            'action' => 'criar',
            'manifestacao' => null,
            'reclamadas' => $reclamadaModel->listar((int) $empresa)->getResult() ?? [],
            'reclamantes' => $reclamanteModel->listar((int) $empresa)->getResult() ?? [],
            'peritos' => $peritoModel->getPeritosAtivos((int) $empresa)->getResult() ?? [],
            'tipos' => $manifestacaoModel->getTiposDistinct((int) $empresa)->getResult() ?? [],
            'usuarios' => $equipeModel->getUsuariosAtivos((int) $empresa)->getResult() ?? [],
            'tarefa' => null,
        ]);
    }

    public function salvarCriar($params)
    {
        // Definir header JSON no início para evitar corrupção
        header('Content-Type: application/json; charset=utf-8');
        
        $this->setParams($params);
        $this->requirePermission('manifestacao_impugnacao_criar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            // Limpar qualquer output buffer antes de enviar JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }
            $this->responseJson(['success' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        $data = $_POST['data'] ?? '';
        $tipo = $_POST['tipo'] ?? '';

        if (empty($data) || empty($tipo)) {
            $this->responseJson(['success' => false, 'message' => 'Data e Tipo são obrigatórios.']);
            return;
        }

        if (!empty($tipo)) {
            $tipo = mb_strtoupper($tipo, 'UTF-8');
        }

        // Salvar o nome completo da situação (sem conversão)
        $favoravel = $_POST['favoravel'] ?? null;
        if (empty($favoravel)) {
            $favoravel = null;
        }

        $dados = [
            'empresa' => (int) $empresa,
            'data' => $data,
            'tipo' => $tipo,
            'numero' => $_POST['numero'] !== '' ? $_POST['numero'] : null,
            'reclamada_id' => !empty($_POST['reclamada_id']) ? (int) $_POST['reclamada_id'] : null,
            'reclamante_id' => !empty($_POST['reclamante_id']) ? (int) $_POST['reclamante_id'] : null,
            'favoravel' => $favoravel,
            'perito_id' => !empty($_POST['perito_id']) ? (int) $_POST['perito_id'] : null,
            'funcao_observacao' => $_POST['funcao_observacao'] !== '' ? $_POST['funcao_observacao'] : null,
        ];

        $model = new ManifestacaoImpugnacao();
        $result = $model->criar($dados);

        if (!$result->getResult()) {
            // Limpar qualquer output buffer antes de enviar JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }
            $this->responseJson(['success' => false, 'message' => 'Erro ao cadastrar.']);
            return;
        }

        $idManifestacao = (int) $result->getResult();

        // Salvar tarefa se fornecida (não bloqueia o cadastro se falhar)
        try {
            $temDadosTarefa = isset($_POST['tarefa_concluido']) || !empty($_POST['tarefa_usuario_responsavel_id']) || !empty($_POST['tarefa_data_conclusao']);
            
            if ($temDadosTarefa) {
                $tarefaModel = new Tarefa();
                $tarefaModel->salvarTarefa('manifestacao', $idManifestacao, (int) $empresa, [
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
            $this->enviarNotificacaoEmailManifestacao($empresa, $idManifestacao, $dados, 'criar');
        } catch (\Exception $e) {
            // Erro silencioso no envio de email
        }

        // Limpar qualquer output buffer antes de enviar JSON
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        $this->responseJson(['success' => true, 'message' => 'Manifestação/Impugnação cadastrada com sucesso.']);
    }

    public function editar($params)
    {
        $this->setParams($params);
        $this->requirePermission('manifestacao_impugnacao_editar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($params['id']) ? (int) $params['id'] : null;

        if (!$empresa || !$id) {
            $this->redirectUrl(DOMAIN . '/manifestacoes-impugnacoes');
            return;
        }

        $model = new ManifestacaoImpugnacao();
        $manifestacao = $model->getPorId($id, (int) $empresa);

        if (!$manifestacao->getResult()) {
            $this->redirectUrl(DOMAIN . '/manifestacoes-impugnacoes');
            return;
        }

        $reclamadaModel = new Reclamada();
        $reclamanteModel = new Reclamante();
        $peritoModel = new Perito();
        $manifestacaoModel = new ManifestacaoImpugnacao();
        $equipeModel = new Equipe();
        $tarefaModel = new Tarefa();

        // Buscar tarefa existente
        $tarefaRead = $tarefaModel->getPorModuloRegistro('manifestacao', $id, (int) $empresa);
        $tarefa = $tarefaRead->getResult()[0] ?? null;

        $this->render('pages/manifestacao_impugnacao/form.twig', [
            'titulo' => 'Editar Manifestação/Impugnação',
            'page' => 'manifestacoes_impugnacoes',
            'action' => 'editar',
            'manifestacao' => $manifestacao->getResult()[0],
            'reclamadas' => $reclamadaModel->listar((int) $empresa)->getResult() ?? [],
            'reclamantes' => $reclamanteModel->listar((int) $empresa)->getResult() ?? [],
            'peritos' => $peritoModel->getPeritosAtivos((int) $empresa)->getResult() ?? [],
            'tipos' => $manifestacaoModel->getTiposDistinct((int) $empresa)->getResult() ?? [],
            'usuarios' => $equipeModel->getUsuariosAtivos((int) $empresa)->getResult() ?? [],
            'tarefa' => $tarefa,
        ]);
    }

    public function salvarEditar($params)
    {
        // Definir header JSON no início para evitar corrupção
        header('Content-Type: application/json; charset=utf-8');
        
        $this->setParams($params);
        $this->requirePermission('manifestacao_impugnacao_editar');

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

        $data = $_POST['data'] ?? '';
        $tipo = $_POST['tipo'] ?? '';

        if (empty($data) || empty($tipo)) {
            // Limpar qualquer output buffer antes de enviar JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }
            $this->responseJson(['success' => false, 'message' => 'Data e Tipo são obrigatórios.']);
            return;
        }

        if (!empty($tipo)) {
            $tipo = mb_strtoupper($tipo, 'UTF-8');
        }

        // Salvar o nome completo da situação (sem conversão)
        $favoravel = $_POST['favoravel'] ?? null;
        if (empty($favoravel)) {
            $favoravel = null;
        }

        $dados = [
            'data' => $data,
            'tipo' => $tipo,
            'numero' => $_POST['numero'] !== '' ? $_POST['numero'] : null,
            'reclamada_id' => !empty($_POST['reclamada_id']) ? (int) $_POST['reclamada_id'] : null,
            'reclamante_id' => !empty($_POST['reclamante_id']) ? (int) $_POST['reclamante_id'] : null,
            'favoravel' => $favoravel,
            'perito_id' => !empty($_POST['perito_id']) ? (int) $_POST['perito_id'] : null,
            'funcao_observacao' => $_POST['funcao_observacao'] !== '' ? $_POST['funcao_observacao'] : null,
        ];

        $model = new ManifestacaoImpugnacao();
        $result = $model->atualizar($id, (int) $empresa, $dados);

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
                $tarefaModel->salvarTarefa('manifestacao', $id, (int) $empresa, [
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
            $this->enviarNotificacaoEmailManifestacao($empresa, $id, $dados, 'editar');
        } catch (\Exception $e) {
            // Erro silencioso no envio de email
        }

        // Limpar qualquer output buffer antes de enviar JSON
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        $this->responseJson(['success' => true, 'message' => 'Manifestação/Impugnação atualizada com sucesso.']);
    }

    private function enviarNotificacaoEmailManifestacao(int $empresa, int $idManifestacao, array $dados, string $acao): void
    {
        try {
            $reclamadaModel = new Reclamada();
            $reclamanteModel = new Reclamante();
            $peritoModel = new Perito();
            
            $reclamadaNome = 'N/A';
            $reclamanteNome = 'N/A';
            $peritoNome = 'N/A';
            
            if (!empty($dados['reclamada_id'])) {
                $reclamadaRead = $reclamadaModel->getPorId($dados['reclamada_id'], $empresa);
                $reclamadaNome = $reclamadaRead->getResult()[0]['nome'] ?? 'N/A';
            }
            
            if (!empty($dados['reclamante_id'])) {
                $reclamanteRead = $reclamanteModel->getPorId($dados['reclamante_id'], $empresa);
                $reclamanteNome = $reclamanteRead->getResult()[0]['nome'] ?? 'N/A';
            }
            
            if (!empty($dados['perito_id'])) {
                $peritoRead = $peritoModel->getPerito($dados['perito_id'], $empresa);
                $peritoNome = $peritoRead->getResult()[0]['nome'] ?? 'N/A';
            }

            $dadosEmail = [
                'titulo' => $acao === 'criar' ? 'Nova Manifestação/Impugnação Cadastrada' : 'Manifestação/Impugnação Atualizada',
                'modulo' => 'Manifestação/Impugnação',
                'acao' => $acao === 'criar' ? 'Criada' : 'Editada',
                'detalhes' => sprintf(
                    '<p><strong>Data:</strong> %s</p><p><strong>Tipo:</strong> %s</p><p><strong>N°:</strong> %s</p><p><strong>Reclamada:</strong> %s</p><p><strong>Reclamante:</strong> %s</p><p><strong>Fav/Desfav:</strong> %s</p><p><strong>Perito:</strong> %s</p>',
                    date('d/m/Y', strtotime($dados['data'])),
                    htmlspecialchars($dados['tipo'] ?? 'N/A'),
                    htmlspecialchars($dados['numero'] ?? 'N/A'),
                    htmlspecialchars($reclamadaNome),
                    htmlspecialchars($reclamanteNome),
                    htmlspecialchars($dados['favoravel'] ?? 'N/A'),
                    htmlspecialchars($peritoNome)
                ),
                'mensagem' => sprintf(
                    'Manifestação/Impugnação do tipo %s foi %s.',
                    htmlspecialchars($dados['tipo'] ?? ''),
                    $acao === 'criar' ? 'cadastrada' : 'atualizada'
                ),
                'url' => DOMAIN . '/manifestacoes-impugnacoes/editar/' . $idManifestacao
            ];

            $emailService = new EmailNotificationService();
            $tipo = $acao === 'criar' ? 'manifestacao_criar' : 'manifestacao_editar';
            $permissoes = ['manifestacao_impugnacao_ver', 'manifestacao_impugnacao_editar'];
            
            $emailService->criarNotificacaoEEmail($tipo, 'manifestacao_impugnacao', $acao, $permissoes, $dadosEmail, $empresa, $idManifestacao, true);
        } catch (\Exception $e) {
            error_log('Erro ao enviar notificação por e-mail: ' . $e->getMessage());
        }
    }

    public function datatable($params)
    {
        $this->setParams($params);
        $this->requirePermission('manifestacao_impugnacao_ver');

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
        if (!empty($_GET['tipo'])) {
            $filtros['tipo'] = $_GET['tipo'];
        }
        if (!empty($_GET['data_inicio'])) {
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }
        if (!empty($_GET['data_fim'])) {
            $filtros['data_fim'] = $_GET['data_fim'];
        }
        if (!empty($_GET['favoravel'])) {
            $filtros['favoravel'] = $_GET['favoravel'];
        }
        if (!empty($_GET['reclamada_id'])) {
            $filtros['reclamada_id'] = $_GET['reclamada_id'];
        }
        if (!empty($_GET['reclamante_id'])) {
            $filtros['reclamante_id'] = $_GET['reclamante_id'];
        }
        if (!empty($_GET['perito_id'])) {
            $filtros['perito_id'] = $_GET['perito_id'];
        }

        $model = new ManifestacaoImpugnacao();
        $result = $model->getManifestacoesDataTable((int) $empresa, $dtParams, $filtros);

        $formattedData = [];
        foreach ($result['data'] as $m) {
            $formattedData[] = [
                date('d/m/Y', strtotime($m['data'])),
                htmlspecialchars($m['tipo'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($m['numero'] ?? '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($m['reclamada_nome'] ?? '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($m['reclamante_nome'] ?? '-', ENT_QUOTES, 'UTF-8'),
                $this->formatFavoravelBadge($m['favoravel'] ?? null),
                htmlspecialchars($m['perito_nome'] ?? '-', ENT_QUOTES, 'UTF-8'),
                $this->formatAcoesCell($m['id'] ?? null),
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

    private function formatFavoravelBadge(?string $favoravel): string
    {
        if (!$favoravel) {
            return '<span class="opacity-50">-</span>';
        }

        // Determinar cor do badge baseado no nome completo
        $badgeClass = 'bg-secondary'; // Padrão
        if ($favoravel === 'Favorável') {
            $badgeClass = 'bg-success';
        } elseif ($favoravel === 'Desfavorável') {
            $badgeClass = 'bg-danger';
        } elseif ($favoravel === 'Parcialmente Favorável') {
            $badgeClass = 'bg-warning';
        }

        return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($favoravel, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    private function formatAcoesCell(?int $id): string
    {
        if (!$id) {
            return '';
        }

        $html = '<div class="d-flex">';
        $html .= '<a href="' . DOMAIN . '/manifestacoes-impugnacoes/editar/' . $id . '" ';
        $html .= 'class="btn btn-success shadow btn-xs sharp me-1" ';
        $html .= 'data-bs-toggle="tooltip" data-bs-title="Editar">';
        $html .= '<i class="fa fa-pencil"></i></a>';
        $html .= '</div>';
        return $html;
    }
}
