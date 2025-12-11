<?php

namespace Agencia\Close\Controllers\Equipe;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Equipe\Equipe;
use Agencia\Close\Models\Cargos\Cargos;

class EquipeController extends Controller
{	
  public function index($params)
  {
    $this->setParams($params);
    $this->requirePermission('equipe_ver');
    
    // Busca a empresa logada
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->redirectUrl(DOMAIN . '/login');
      return;
    }

    // Lista os membros da equipe
    $model = new Equipe();
    $equipe = $model->getEquipe($empresa);
    
    $this->render('pages/equipe/index.twig', [
      'titulo' => 'Lista de Equipe',
      'page' => 'equipe',
      'equipe' => $equipe->getResult() ?? []
    ]);
  }

  public function criar($params)
  {
    $this->setParams($params);
    $this->requirePermission('equipe_criar');
    
    // Busca cargos disponíveis
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    $cargosModel = new Cargos();
    $cargos = $cargosModel->getCargos($empresa);
    
    $this->render('pages/equipe/form.twig', [
      'titulo' => 'Criar Membro da Equipe',
      'page' => 'equipe',
      'action' => 'criar',
      'cargos' => $cargos->getResult() ?? []
    ]);
  }

  public function editar($params)
  {
    $this->setParams($params);
    
    $id = $params['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->redirectUrl(DOMAIN . '/equipe');
      return;
    }

    // Busca o membro da equipe
    $model = new Equipe();
    $membro = $model->getMembroEquipe($id, $empresa);
    
    if (!$membro->getResult()) {
      $this->redirectUrl(DOMAIN . '/equipe');
      return;
    }

    // Busca cargos disponíveis
    $cargosModel = new Cargos();
    $cargos = $cargosModel->getCargos($empresa);
    
    $this->render('pages/equipe/form.twig', [
      'titulo' => 'Editar Membro da Equipe',
      'page' => 'equipe',
      'action' => 'editar',
      'membro' => $membro->getResult()[0] ?? null,
      'cargos' => $cargos->getResult() ?? []
    ]);
  }

  public function criarSalvar($params)
  {
    // Definir header JSON no início para evitar corrupção
    header('Content-Type: application/json; charset=utf-8');
    
    $this->setParams($params);
    $this->requirePermission('equipe_criar');
    
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Empresa não encontrada']);
      return;
    }

    // Valida dados obrigatórios
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($nome) || empty($email) || empty($senha)) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
      return;
    }

    // Verifica se email já existe
    $model = new Equipe();
    $emailExistente = $model->emailExiste($email, $empresa);
    
    if ($emailExistente->getResult()) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Este email já está em uso']);
      return;
    }

    // Prepara dados para inserção
    $data = [
      'empresa' => $empresa,
      'tipo' => 3, // Tipo equipe
      'nome' => $nome,
      'email' => $email,
      'telefone' => $telefone,
      'cargo' => $cargo,
      'senha' => sha1($senha),
      'status' => 'Ativo'
    ];

    // Cria o membro da equipe
    $result = $model->criarMembroEquipe($data);
    
    if (!$result->getResult()) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Erro ao criar membro da equipe']);
      return;
    }

    $usuarioId = (int) $result->getResult();
    
    // Copia permissões do cargo para o usuário (não bloqueia o cadastro se falhar)
    try {
      if (!empty($cargo)) {
        $model->copiarPermissoesDoCargo($usuarioId, $cargo, $empresa);
      }
    } catch (\Exception $e) {
      // Erro silencioso na cópia de permissões
    } catch (\Error $e) {
      // Erro silencioso na cópia de permissões
    }
    
    // Limpar qualquer output buffer antes de enviar JSON
    if (ob_get_level() > 0) {
      ob_clean();
    }
    
    $this->responseJson(['success' => true, 'message' => 'Membro da equipe criado com sucesso']);
  }

  public function editarSalvar($params)
  {
    // Definir header JSON no início para evitar corrupção
    header('Content-Type: application/json; charset=utf-8');
    
    $this->setParams($params);
    $this->requirePermission('equipe_editar');
    
    $id = $_POST['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
      return;
    }

    // Valida dados obrigatórios
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($nome) || empty($email)) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
      return;
    }

    // Verifica se email já existe para outro usuário
    $model = new Equipe();
    $emailExistente = $model->emailExiste($email, $empresa, $id);
    
    if ($emailExistente->getResult()) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Este email já está em uso']);
      return;
    }

    // Prepara dados para atualização
    $data = [
      'nome' => $nome,
      'email' => $email,
      'telefone' => $telefone,
      'cargo' => $cargo
    ];

    // Se senha foi informada, inclui na atualização
    if (!empty($senha)) {
      $data['senha'] = sha1($senha);
    }

    // Busca o cargo anterior para verificar se mudou
    $membroAtual = $model->getMembroEquipe($id, $empresa);
    $cargoAnterior = $membroAtual->getResult()[0]['cargo'] ?? null;
    
    // Atualiza o membro da equipe
    $result = $model->atualizarMembroEquipe($id, $data, $empresa);
    
    if (!$result->getResult()) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar membro da equipe']);
      return;
    }

    // Se o cargo mudou, atualiza as permissões (não bloqueia a atualização se falhar)
    try {
      if ($cargo !== $cargoAnterior) {
        $model->copiarPermissoesDoCargo((int) $id, $cargo, $empresa);
      }
    } catch (\Exception $e) {
      // Erro silencioso na cópia de permissões
    } catch (\Error $e) {
      // Erro silencioso na cópia de permissões
    }
    
    // Limpar qualquer output buffer antes de enviar JSON
    if (ob_get_level() > 0) {
      ob_clean();
    }
    
    $this->responseJson(['success' => true, 'message' => 'Membro da equipe atualizado com sucesso']);
  }

  public function remover($params)
  {
    $this->setParams($params);
    $this->requirePermission('equipe_deletar');
    
    $id = $_POST['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
      return;
    }

    // Verifica se o membro existe antes de remover
    $model = new Equipe();
    $membro = $model->getMembroEquipe($id, $empresa);
    
    if (!$membro->getResult()) {
      $this->responseJson(['success' => false, 'message' => 'Membro da equipe não encontrado']);
      return;
    }

    // Remove o membro da equipe permanentemente do banco de dados
    $result = $model->removerMembroEquipe($id, $empresa);
    
    if ($result->getResult()) {
      $this->responseJson(['success' => true, 'message' => 'Membro da equipe removido permanentemente']);
    } else {
      $this->responseJson(['success' => false, 'message' => 'Erro ao remover membro da equipe']);
    }
  }

}