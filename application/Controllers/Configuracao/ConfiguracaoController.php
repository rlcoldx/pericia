<?php

namespace Agencia\Close\Controllers\Configuracao;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Configuracao\Configuracao;

class ConfiguracaoController extends Controller
{
    public function index($params)
    {
        $this->setParams($params);
        $this->requirePermission('configuracao_ver');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $model = new Configuracao();
        $configRead = $model->getPorEmpresa((int) $empresa);
        $configuracao = $configRead->getResult()[0] ?? null;

        // Se não existir, usa valores padrão
        if (!$configuracao) {
            $configuracao = $model->getConfiguracoesOuPadrao((int) $empresa);
        }

        $this->render('pages/configuracao/form.twig', [
            'titulo' => 'Configurações',
            'page' => 'configuracao',
            'configuracao' => $configuracao,
            'action' => $configuracao && isset($configuracao['id']) ? 'editar' : 'criar'
        ]);
    }

    public function salvar($params)
    {
        $this->setParams($params);
        $this->requirePermission('configuracao_editar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        // Validações
        $mailHost = trim($_POST['mail_host'] ?? '');
        $mailEmail = trim($_POST['mail_email'] ?? '');
        $mailUser = trim($_POST['mail_user'] ?? '');
        $mailPassword = trim($_POST['mail_password'] ?? '');

        if (empty($mailHost)) {
            $this->responseJson(['success' => false, 'message' => 'Host do servidor SMTP é obrigatório.']);
            return;
        }

        if (empty($mailEmail)) {
            $this->responseJson(['success' => false, 'message' => 'Email é obrigatório.']);
            return;
        }

        if (empty($mailUser)) {
            $this->responseJson(['success' => false, 'message' => 'Usuário SMTP é obrigatório.']);
            return;
        }

        if (empty($mailPassword)) {
            $this->responseJson(['success' => false, 'message' => 'Senha SMTP é obrigatória.']);
            return;
        }

        // Valida formato de email
        if (!filter_var($mailEmail, FILTER_VALIDATE_EMAIL)) {
            $this->responseJson(['success' => false, 'message' => 'Email inválido.']);
            return;
        }

        // Valida emails CC se fornecidos
        $mailCc = trim($_POST['mail_cc'] ?? '');
        if (!empty($mailCc)) {
            $emailsCc = array_map('trim', explode(',', $mailCc));
            foreach ($emailsCc as $email) {
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->responseJson(['success' => false, 'message' => "Email CC inválido: {$email}"]);
                    return;
                }
            }
        }

        $model = new Configuracao();
        $configRead = $model->getPorEmpresa((int) $empresa);
        $configuracaoExistente = $configRead->getResult()[0] ?? null;

        $dados = [
            'empresa' => (int) $empresa,
            'mail_host' => $mailHost,
            'mail_email' => $mailEmail,
            'mail_user' => $mailUser,
            'mail_password' => $mailPassword,
            'mail_cc' => !empty($mailCc) ? $mailCc : null,
        ];

        if ($configuracaoExistente) {
            // Atualiza
            $result = $model->atualizar(
                (int) $configuracaoExistente['id'],
                (int) $empresa,
                $dados
            );
        } else {
            // Cria
            $result = $model->criar($dados);
        }

        if ($result->getResult()) {
            $this->responseJson(['success' => true, 'message' => 'Configurações salvas com sucesso!']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao salvar configurações.']);
        }
    }
}
