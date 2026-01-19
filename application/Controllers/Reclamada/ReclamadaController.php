<?php

namespace Agencia\Close\Controllers\Reclamada;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Reclamada\Reclamada;
use Agencia\Close\Services\Notificacao\EmailNotificationService;
use Agencia\Close\Helpers\DataTableResponse;

class ReclamadaController extends Controller
{
    public function index($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamadas_ver');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $model = new Reclamada();
        $lista = $model->listar((int) $empresa);

        $this->render('pages/reclamada/index.twig', [
            'titulo' => 'Reclamadas',
            'page' => 'reclamadas',
            'reclamadas' => $lista->getResult() ?? [],
        ]);
    }

    public function criar($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamadas_criar');

        $this->render('pages/reclamada/form.twig', [
            'titulo' => 'Nova Reclamada',
            'page' => 'reclamadas',
            'action' => 'criar',
            'reclamada' => null,
        ]);
    }

    public function salvarCriar($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamadas_criar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $nome = trim($_POST['nome'] ?? '');

        if (empty($nome)) {
            $this->responseJson(['success' => false, 'message' => 'Nome é obrigatório.']);
            return;
        }

        $model = new Reclamada();
        $result = $model->criar([
            'empresa' => (int) $empresa,
            'nome' => $nome,
            'nome_contato' => !empty($_POST['nome_contato']) ? trim($_POST['nome_contato']) : null,
            'email_contato' => !empty($_POST['email_contato']) ? trim($_POST['email_contato']) : null,
            'telefone_contato' => !empty($_POST['telefone_contato']) ? trim($_POST['telefone_contato']) : null,
        ]);

        if ($result->getResult()) {
            $idReclamada = (int) $result->getResult();
            $this->enviarNotificacaoEmailReclamada($empresa, $idReclamada, ['nome' => $nome], 'criar');
            $this->responseJson(['success' => true, 'message' => 'Reclamada cadastrada com sucesso.']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao cadastrar reclamada.']);
        }
    }

    public function editar($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamadas_editar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($params['id']) ? (int) $params['id'] : null;

        if (!$empresa || !$id) {
            $this->redirectUrl(DOMAIN . '/reclamadas');
            return;
        }

        $model = new Reclamada();
        $reclamada = $model->getPorId($id, (int) $empresa);

        if (!$reclamada->getResult()) {
            $this->redirectUrl(DOMAIN . '/reclamadas');
            return;
        }

        $this->render('pages/reclamada/form.twig', [
            'titulo' => 'Editar Reclamada',
            'page' => 'reclamadas',
            'action' => 'editar',
            'reclamada' => $reclamada->getResult()[0],
        ]);
    }

    public function salvarEditar($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamadas_editar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $nome = trim($_POST['nome'] ?? '');

        if (!$empresa || !$id || empty($nome)) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $model = new Reclamada();
        $result = $model->atualizar($id, (int) $empresa, [
            'nome' => $nome,
            'nome_contato' => !empty($_POST['nome_contato']) ? trim($_POST['nome_contato']) : null,
            'email_contato' => !empty($_POST['email_contato']) ? trim($_POST['email_contato']) : null,
            'telefone_contato' => !empty($_POST['telefone_contato']) ? trim($_POST['telefone_contato']) : null,
        ]);

        if ($result->getResult()) {
            $this->enviarNotificacaoEmailReclamada($empresa, $id, ['nome' => $nome], 'editar');
            $this->responseJson(['success' => true, 'message' => 'Reclamada atualizada com sucesso.']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar reclamada.']);
        }
    }

    private function enviarNotificacaoEmailReclamada(int $empresa, int $idReclamada, array $dados, string $acao): void
    {
        try {
            $dadosEmail = [
                'titulo' => $acao === 'criar' ? 'Nova Reclamada Cadastrada' : 'Reclamada Atualizada',
                'modulo' => 'Reclamada',
                'acao' => $acao === 'criar' ? 'Criada' : 'Editada',
                'detalhes' => sprintf('<p><strong>Nome:</strong> %s</p>', htmlspecialchars($dados['nome'] ?? 'N/A')),
                'mensagem' => sprintf('Reclamada %s foi %s.', htmlspecialchars($dados['nome'] ?? ''), $acao === 'criar' ? 'cadastrada' : 'atualizada'),
                'url' => DOMAIN . '/reclamadas'
            ];

            $emailService = new EmailNotificationService();
            $tipo = $acao === 'criar' ? 'reclamada_criar' : 'reclamada_editar';
            $permissoes = ['reclamada_ver', 'reclamada_editar'];
            
            $emailService->criarNotificacaoEEmail($tipo, 'reclamada', $acao, $permissoes, $dadosEmail, $empresa, $idReclamada, true);
        } catch (\Exception $e) {
            error_log('Erro ao enviar notificação por e-mail: ' . $e->getMessage());
        }
    }

    public function remover($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamadas_deletar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;

        if (!$empresa || !$id) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $model = new Reclamada();
        $result = $model->remover($id, (int) $empresa);

        if ($result->getResult()) {
            $this->responseJson(['success' => true, 'message' => 'Reclamada removida com sucesso.']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao remover reclamada.']);
        }
    }

    public function datatable($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamadas_ver');

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

        $model = new Reclamada();
        $result = $model->getReclamadasDataTable((int) $empresa, $dtParams, $filtros);

        $formattedData = [];
        foreach ($result['data'] as $r) {
            $formattedData[] = [
                htmlspecialchars($r['nome'] ?? '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($r['nome_contato'] ?: '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($r['email_contato'] ?: '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($r['telefone_contato'] ?: '-', ENT_QUOTES, 'UTF-8'),
                $this->formatAcoesCell($r['id'] ?? null, $r['nome'] ?? ''),
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

    /**
     * Cria uma reclamada rapidamente via AJAX (usado em formulários de outros módulos)
     */
    public function criarRapido($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamadas_criar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $nome = trim($_POST['nome'] ?? '');

        if (empty($nome)) {
            $this->responseJson(['success' => false, 'message' => 'Nome é obrigatório.']);
            return;
        }

        // Verificar se já existe uma reclamada com esse nome
        $model = new Reclamada();
        $read = new \Agencia\Close\Conn\Read();
        $read->ExeRead(
            'reclamadas',
            'WHERE empresa = :empresa AND nome = :nome',
            "empresa={$empresa}&nome={$nome}"
        );

        if ($read->getResult()) {
            // Já existe, retorna o ID existente
            $existente = $read->getResult()[0];
            $this->responseJson([
                'success' => true,
                'id' => (int) $existente['id'],
                'nome' => $existente['nome'],
                'message' => 'Reclamada já existe.'
            ]);
            return;
        }

        // Criar nova
        $result = $model->criar([
            'empresa' => (int) $empresa,
            'nome' => $nome,
        ]);

        if ($result->getResult()) {
            $idReclamada = (int) $result->getResult();
            $this->responseJson([
                'success' => true,
                'id' => $idReclamada,
                'nome' => $nome,
                'message' => 'Reclamada criada com sucesso.'
            ]);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao criar reclamada.']);
        }
    }

    private function formatAcoesCell(?int $id, string $nome = ''): string
    {
        if (!$id) {
            return '';
        }

        $permissionService = new \Agencia\Close\Services\Login\PermissionsService();

        $html = '<div class="d-flex">';
        if ($permissionService->verifyPermissions('reclamadas_editar')) {
            $html .= '<a href="' . DOMAIN . '/reclamadas/editar/' . $id . '" ';
            $html .= 'class="btn btn-success shadow btn-xs sharp me-1" ';
            $html .= 'data-bs-toggle="tooltip" data-bs-title="Editar">';
            $html .= '<i class="fa fa-pencil"></i></a>';
        }
        if ($permissionService->verifyPermissions('reclamadas_deletar')) {
            $nomeEscapado = htmlspecialchars(addslashes($nome ?: 'Reclamada'), ENT_QUOTES, 'UTF-8');
            $html .= '<button type="button" class="btn btn-danger shadow btn-xs sharp" ';
            $html .= 'onclick="removerReclamada(' . $id . ', \'' . $nomeEscapado . '\')" ';
            $html .= 'data-bs-toggle="tooltip" data-bs-title="Remover">';
            $html .= '<i class="fa fa-trash"></i></button>';
        }
        $html .= '</div>';
        return $html;
    }
}
