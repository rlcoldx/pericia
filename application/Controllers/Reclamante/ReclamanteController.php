<?php

namespace Agencia\Close\Controllers\Reclamante;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Reclamante\Reclamante;
use Agencia\Close\Services\Notificacao\EmailNotificationService;

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
        $result = $model->atualizar($id, (int) $empresa, ['nome' => $nome]);

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
}
