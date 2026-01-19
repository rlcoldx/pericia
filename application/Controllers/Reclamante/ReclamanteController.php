<?php

namespace Agencia\Close\Controllers\Reclamante;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Reclamante\Reclamante;
use Agencia\Close\Services\Notificacao\EmailNotificationService;
use Agencia\Close\Helpers\DataTableResponse;

class ReclamanteController extends Controller
{
    public function index($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamantes_ver');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $model = new Reclamante();
        $lista = $model->listar((int) $empresa);

        $this->render('pages/reclamante/index.twig', [
            'titulo' => 'Reclamantes',
            'page' => 'reclamantes',
            'reclamantes' => $lista->getResult() ?? [],
        ]);
    }

    public function criar($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamantes_criar');

        $this->render('pages/reclamante/form.twig', [
            'titulo' => 'Novo Reclamante',
            'page' => 'reclamantes',
            'action' => 'criar',
            'reclamante' => null,
        ]);
    }

    public function salvarCriar($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamantes_criar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $nome = trim($_POST['nome'] ?? '');

        if (empty($nome)) {
            $this->responseJson(['success' => false, 'message' => 'Nome é obrigatório.']);
            return;
        }

        $model = new Reclamante();
        $result = $model->criar([
            'empresa' => (int) $empresa,
            'nome' => $nome,
            'nome_contato' => !empty($_POST['nome_contato']) ? trim($_POST['nome_contato']) : null,
            'email_contato' => !empty($_POST['email_contato']) ? trim($_POST['email_contato']) : null,
            'telefone_contato' => !empty($_POST['telefone_contato']) ? trim($_POST['telefone_contato']) : null,
        ]);

        if ($result->getResult()) {
            $idReclamante = (int) $result->getResult();
            $this->enviarNotificacaoEmailReclamante($empresa, $idReclamante, ['nome' => $nome], 'criar');
            $this->responseJson(['success' => true, 'message' => 'Reclamante cadastrado com sucesso.']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao cadastrar reclamante.']);
        }
    }

    public function editar($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamantes_editar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($params['id']) ? (int) $params['id'] : null;

        if (!$empresa || !$id) {
            $this->redirectUrl(DOMAIN . '/reclamantes');
            return;
        }

        $model = new Reclamante();
        $reclamante = $model->getPorId($id, (int) $empresa);

        if (!$reclamante->getResult()) {
            $this->redirectUrl(DOMAIN . '/reclamantes');
            return;
        }

        $this->render('pages/reclamante/form.twig', [
            'titulo' => 'Editar Reclamante',
            'page' => 'reclamantes',
            'action' => 'editar',
            'reclamante' => $reclamante->getResult()[0],
        ]);
    }

    public function salvarEditar($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamantes_editar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $nome = trim($_POST['nome'] ?? '');

        if (!$empresa || !$id || empty($nome)) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $model = new Reclamante();
        $result = $model->atualizar($id, (int) $empresa, [
            'nome' => $nome,
            'nome_contato' => !empty($_POST['nome_contato']) ? trim($_POST['nome_contato']) : null,
            'email_contato' => !empty($_POST['email_contato']) ? trim($_POST['email_contato']) : null,
            'telefone_contato' => !empty($_POST['telefone_contato']) ? trim($_POST['telefone_contato']) : null,
        ]);

        if ($result->getResult()) {
            $this->enviarNotificacaoEmailReclamante($empresa, $id, ['nome' => $nome], 'editar');
            $this->responseJson(['success' => true, 'message' => 'Reclamante atualizado com sucesso.']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar reclamante.']);
        }
    }

    private function enviarNotificacaoEmailReclamante(int $empresa, int $idReclamante, array $dados, string $acao): void
    {
        try {
            $dadosEmail = [
                'titulo' => $acao === 'criar' ? 'Novo Reclamante Cadastrado' : 'Reclamante Atualizado',
                'modulo' => 'Reclamante',
                'acao' => $acao === 'criar' ? 'Criado' : 'Editado',
                'detalhes' => sprintf('<p><strong>Nome:</strong> %s</p>', htmlspecialchars($dados['nome'] ?? 'N/A')),
                'mensagem' => sprintf('Reclamante %s foi %s.', htmlspecialchars($dados['nome'] ?? ''), $acao === 'criar' ? 'cadastrado' : 'atualizado'),
                'url' => DOMAIN . '/reclamantes'
            ];

            $emailService = new EmailNotificationService();
            $tipo = $acao === 'criar' ? 'reclamante_criar' : 'reclamante_editar';
            $permissoes = ['reclamante_ver', 'reclamante_editar'];
            
            $emailService->criarNotificacaoEEmail($tipo, 'reclamante', $acao, $permissoes, $dadosEmail, $empresa, $idReclamante, true);
        } catch (\Exception $e) {
            error_log('Erro ao enviar notificação por e-mail: ' . $e->getMessage());
        }
    }

    public function remover($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamantes_deletar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;

        if (!$empresa || !$id) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $model = new Reclamante();
        $result = $model->remover($id, (int) $empresa);

        if ($result->getResult()) {
            $this->responseJson(['success' => true, 'message' => 'Reclamante removido com sucesso.']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao remover reclamante.']);
        }
    }

    public function datatable($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamantes_ver');

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

        $model = new Reclamante();
        $result = $model->getReclamantesDataTable((int) $empresa, $dtParams, $filtros);

        $formattedData = [];
        foreach ($result['data'] as $r) {
            $formattedData[] = [
                htmlspecialchars($r['nome'] ?? '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($r['nome_contato'] ?: '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($r['email_contato'] ?: '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($r['telefone_contato'] ?: '-', ENT_QUOTES, 'UTF-8'),
                $this->formatAcoesCell($r['id'] ?? null),
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
     * Cria um reclamante rapidamente via AJAX (usado em formulários de outros módulos)
     */
    public function criarRapido($params)
    {
        $this->setParams($params);
        $this->requirePermission('reclamantes_criar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $nome = trim($_POST['nome'] ?? '');

        if (empty($nome)) {
            $this->responseJson(['success' => false, 'message' => 'Nome é obrigatório.']);
            return;
        }

        // Verificar se já existe um reclamante com esse nome
        $model = new Reclamante();
        $read = new \Agencia\Close\Conn\Read();
        $read->ExeRead(
            'reclamantes',
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
                'message' => 'Reclamante já existe.'
            ]);
            return;
        }

        // Criar novo
        $result = $model->criar([
            'empresa' => (int) $empresa,
            'nome' => $nome,
        ]);

        if ($result->getResult()) {
            $idReclamante = (int) $result->getResult();
            $this->responseJson([
                'success' => true,
                'id' => $idReclamante,
                'nome' => $nome,
                'message' => 'Reclamante criado com sucesso.'
            ]);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao criar reclamante.']);
        }
    }

    private function formatAcoesCell(?int $id): string
    {
        if (!$id) {
            return '';
        }

        $permissionService = new \Agencia\Close\Services\Login\PermissionsService();

        $html = '<div class="d-flex">';
        if ($permissionService->verifyPermissions('reclamantes_editar')) {
            $html .= '<a href="' . DOMAIN . '/reclamantes/editar/' . $id . '" ';
            $html .= 'class="btn btn-success shadow btn-xs sharp me-1" ';
            $html .= 'data-bs-toggle="tooltip" data-bs-title="Editar">';
            $html .= '<i class="fa fa-pencil"></i></a>';
        }
        if ($permissionService->verifyPermissions('reclamantes_deletar')) {
            $html .= '<button type="button" class="btn btn-danger shadow btn-xs sharp" ';
            $html .= 'onclick="removerReclamante(' . $id . ', \'' . htmlspecialchars('Reclamante', ENT_QUOTES) . '\')" ';
            $html .= 'data-bs-toggle="tooltip" data-bs-title="Remover">';
            $html .= '<i class="fa fa-trash"></i></button>';
        }
        $html .= '</div>';
        return $html;
    }
}
