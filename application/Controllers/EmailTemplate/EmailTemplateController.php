<?php

namespace Agencia\Close\Controllers\EmailTemplate;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\EmailTemplate\EmailTemplate;

class EmailTemplateController extends Controller
{
    protected EmailTemplate $model;

    public function __construct($router)
    {
        parent::__construct($router);
        $this->model = new EmailTemplate();
    }

    public function index()
    {
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $templates = $this->model->listar((int)$empresa);

        $this->render('pages/email_template/index.twig', [
            'page' => 'email_template',
            'titulo' => 'Templates de E-mail',
            'templates' => $templates->getResult() ?? []
        ]);
    }

    public function criar()
    {
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $tipos = $this->getTiposDisponiveis();

        $this->render('pages/email_template/form.twig', [
            'page' => 'email_template',
            'titulo' => 'Criar Template de E-mail',
            'action' => 'criar',
            'template' => null,
            'tipos' => $tipos
        ]);
    }

    public function salvarCriar()
    {
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        $tipo = $_POST['tipo'] ?? '';
        $assunto = $_POST['assunto'] ?? '';
        $corpo = $_POST['corpo'] ?? '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (empty($tipo) || empty($assunto) || empty($corpo)) {
            $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            return;
        }

        $data = [
            'empresa' => (int)$empresa,
            'tipo' => $tipo,
            'assunto' => $assunto,
            'corpo' => $corpo,
            'ativo' => $ativo
        ];

        $result = $this->model->criar($data);

        if ($result->getResult()) {
            $this->responseJson(['success' => true, 'message' => 'Template criado com sucesso!']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao criar template.']);
        }
    }

    public function editar($params)
    {
        $this->setParams($params);
        $id = $params['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$id || !$empresa) {
            $this->redirectUrl(DOMAIN . '/email-templates');
            return;
        }

        $templateRead = $this->model->getPorId((int)$id, (int)$empresa);
        $template = $templateRead->getResult()[0] ?? null;

        if (!$template) {
            $this->redirectUrl(DOMAIN . '/email-templates');
            return;
        }

        $tipos = $this->getTiposDisponiveis();

        $this->render('pages/email_template/form.twig', [
            'page' => 'email_template',
            'titulo' => 'Editar Template de E-mail',
            'action' => 'editar',
            'template' => $template,
            'tipos' => $tipos
        ]);
    }

    public function salvarEditar($params)
    {
        $this->setParams($params);
        $id = $params['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$id || !$empresa) {
            $this->responseJson(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        $assunto = $_POST['assunto'] ?? '';
        $corpo = $_POST['corpo'] ?? '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (empty($assunto) || empty($corpo)) {
            $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
            return;
        }

        $data = [
            'assunto' => $assunto,
            'corpo' => $corpo,
            'ativo' => $ativo
        ];

        $result = $this->model->atualizar((int)$id, $data, (int)$empresa);

        if ($result->getResult()) {
            $this->responseJson(['success' => true, 'message' => 'Template atualizado com sucesso!']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar template.']);
        }
    }

    public function remover($params)
    {
        $this->setParams($params);
        $id = $params['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$id || !$empresa) {
            $this->responseJson(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        $result = $this->model->remover((int)$id, (int)$empresa);

        if ($result->getResult()) {
            $this->responseJson(['success' => true, 'message' => 'Template removido com sucesso!']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao remover template.']);
        }
    }

    protected function getTiposDisponiveis(): array
    {
        return [
            'quesito_criar' => 'Quesito - Criar',
            'quesito_editar' => 'Quesito - Editar',
            'quesito_enviar_cliente' => 'Quesito - Enviar para Cliente',
            'parecer_criar' => 'Parecer - Criar',
            'parecer_editar' => 'Parecer - Editar',
            'manifestacao_criar' => 'Manifestação/Impugnação - Criar',
            'manifestacao_editar' => 'Manifestação/Impugnação - Editar',
            'perito_criar' => 'Perito - Criar',
            'perito_editar' => 'Perito - Editar',
            'agendamento_criar' => 'Agendamento - Criar',
            'agendamento_editar' => 'Agendamento - Editar',
            'reclamada_criar' => 'Reclamada - Criar',
            'reclamada_editar' => 'Reclamada - Editar',
            'reclamante_criar' => 'Reclamante - Criar',
            'reclamante_editar' => 'Reclamante - Editar',
        ];
    }

    public function ativarTodos($params = [])
    {
        $this->setParams($params);
        $this->requirePermission('email_template_gerenciar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        $result = $this->model->ativarTodos((int) $empresa);

        if ($result->getResult()) {
            $this->responseJson(['success' => true, 'message' => 'Todos os templates foram ativados com sucesso!']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao ativar templates.']);
        }
    }

    public function desativarTodos($params = [])
    {
        $this->setParams($params);
        $this->requirePermission('email_template_gerenciar');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

        if (!$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        $result = $this->model->desativarTodos((int) $empresa);

        if ($result->getResult()) {
            $this->responseJson(['success' => true, 'message' => 'Todos os templates foram desativados com sucesso!']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao desativar templates.']);
        }
    }
}
