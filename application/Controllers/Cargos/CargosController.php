<?php

namespace Agencia\Close\Controllers\Cargos;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Cargos\Cargos;

class CargosController extends Controller
{	
  public function index($params)
  {
    $this->setParams($params);
    
    // Busca a empresa logada
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->redirectUrl(DOMAIN . '/login');
      return;
    }

    // Lista os cargos
    $model = new Cargos();
    $cargos = $model->getCargos($empresa);
    
    $this->render('pages/equipe/cargos.twig', [
      'titulo' => 'Lista de Cargos',
      'page' => 'cargos',
      'cargos' => $cargos->getResult() ?? []
    ]);
  }

  public function criar($params)
  {
    $this->setParams($params);
    
    // Busca todas as permissões disponíveis
    $model = new Cargos();
    $permissoes = $model->getPermissoesDisponiveis();
    
    $this->render('pages/equipe/cargo-form.twig', [
      'titulo' => 'Criar Cargo',
      'page' => 'cargos',
      'action' => 'criar',
      'permissoes' => $permissoes->getResult() ?? []
    ]);
  }

  public function editar($params)
  {
    $this->setParams($params);
    
    $id = $params['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->redirectUrl(DOMAIN . '/cargos');
      return;
    }

    // Busca o cargo
    $model = new Cargos();
    $cargo = $model->getCargo($id, $empresa);
    
    if (!$cargo->getResult()) {
      $this->redirectUrl(DOMAIN . '/cargos');
      return;
    }

    // Busca permissões do cargo
    $permissoesCargo = $model->getPermissoesCargo($id);
    $permissoesCargoIds = array_column($permissoesCargo->getResult() ?? [], 'permissao_id');
    
    // Busca todas as permissões disponíveis
    $permissoes = $model->getPermissoesDisponiveis();
    
    $this->render('pages/equipe/cargo-form.twig', [
      'titulo' => 'Editar Cargo',
      'page' => 'cargos',
      'action' => 'editar',
      'cargo' => $cargo->getResult()[0] ?? null,
      'permissoes' => $permissoes->getResult() ?? [],
      'permissoesCargo' => $permissoesCargoIds
    ]);
  }

  public function criarSalvar($params)
  {
    $this->setParams($params);
    
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Empresa não encontrada']);
      return;
    }

    // Valida dados obrigatórios
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $status = $_POST['status'] ?? 'Ativo';
    $permissoes = $_POST['permissoes'] ?? [];

    if (empty($nome)) {
      $this->responseJson(['success' => false, 'message' => 'Nome do cargo é obrigatório']);
      return;
    }

    // Verifica se nome já existe
    $model = new Cargos();
    $nomeExistente = $model->nomeExiste($nome, $empresa);
    
    if ($nomeExistente->getResult()) {
      $this->responseJson(['success' => false, 'message' => 'Este nome de cargo já está em uso']);
      return;
    }

    // Prepara dados para inserção
    $data = [
      'empresa' => $empresa,
      'nome' => $nome,
      'descricao' => $descricao,
      'status' => $status
    ];

    // Cria o cargo
    $result = $model->criarCargo($data);
    
    if ($result->getResult()) {
      $cargoId = $result->getResult();
      
      // Salva as permissões do cargo
      $model->salvarPermissoesCargo($cargoId, $permissoes);
      
      $this->responseJson(['success' => true, 'message' => 'Cargo criado com sucesso']);
    } else {
      $this->responseJson(['success' => false, 'message' => 'Erro ao criar cargo']);
    }
  }

  public function editarSalvar($params)
  {
    $this->setParams($params);
    
    $id = $_POST['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
      return;
    }

    // Valida dados obrigatórios
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $status = $_POST['status'] ?? 'Ativo';
    $permissoes = $_POST['permissoes'] ?? [];

    if (empty($nome)) {
      $this->responseJson(['success' => false, 'message' => 'Nome do cargo é obrigatório']);
      return;
    }

    // Verifica se nome já existe para outro cargo
    $model = new Cargos();
    $nomeExistente = $model->nomeExiste($nome, $empresa, $id);
    
    if ($nomeExistente->getResult()) {
      $this->responseJson(['success' => false, 'message' => 'Este nome de cargo já está em uso']);
      return;
    }

    // Prepara dados para atualização
    $data = [
      'nome' => $nome,
      'descricao' => $descricao,
      'status' => $status
    ];

    // Atualiza o cargo
    $result = $model->atualizarCargo($id, $data, $empresa);
    
    if ($result->getResult()) {
      // Atualiza as permissões do cargo
      $model->salvarPermissoesCargo($id, $permissoes);
      
      $this->responseJson(['success' => true, 'message' => 'Cargo atualizado com sucesso']);
    } else {
      $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar cargo']);
    }
  }

  public function remover($params)
  {
    $this->setParams($params);
    
    $id = $_POST['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
      return;
    }

    // Verifica se o cargo existe antes de remover
    $model = new Cargos();
    $cargo = $model->getCargo($id, $empresa);
    
    if (!$cargo->getResult()) {
      $this->responseJson(['success' => false, 'message' => 'Cargo não encontrado']);
      return;
    }

    // Remove o cargo
    $result = $model->removerCargo($id, $empresa);
    
    if ($result->getResult()) {
      $this->responseJson(['success' => true, 'message' => 'Cargo removido com sucesso']);
    } else {
      $this->responseJson(['success' => false, 'message' => 'Erro ao remover cargo']);
    }
  }

  public function verPermissoes($params)
  {
    $this->setParams($params);
    
    $id = $params['id'] ?? null;
    
    if (!$id) {
      $this->responseJson(['success' => false, 'message' => 'ID do cargo inválido']);
      return;
    }

    // Busca permissões do cargo
    $model = new Cargos();
    $permissoesCargo = $model->getPermissoesCargo($id);
    $permissoesIds = array_column($permissoesCargo->getResult() ?? [], 'permissao_id');
    
    if (empty($permissoesIds)) {
      $this->responseJson(['success' => true, 'permissoes' => []]);
      return;
    }
    
    // Busca detalhes das permissões
    $permissoes = $model->getPermissoesDisponiveis();
    $permissoesDetalhes = [];
    
    foreach ($permissoes->getResult() as $permissao) {
      if (in_array($permissao['id'], $permissoesIds)) {
        $permissoesDetalhes[] = $permissao;
      }
    }
    
    $this->responseJson(['success' => true, 'permissoes' => $permissoesDetalhes]);
  }

}

