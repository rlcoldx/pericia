<?php

namespace Agencia\Close\Controllers\Perito;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Perito\Perito;
use Agencia\Close\Services\Notificacao\EmailNotificationService;
use Agencia\Close\Helpers\DataTableResponse;

class PeritoController extends Controller
{
  public function index($params)
  {
    $this->setParams($params);
    $this->requirePermission('perito_ver');
    
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->redirectUrl(DOMAIN . '/login');
      return;
    }

    $this->render('pages/perito/index.twig', [
      'titulo' => 'Peritos',
      'page' => 'perito'
    ]);
  }

  public function criar($params)
  {
    $this->setParams($params);
    $this->requirePermission('perito_criar');
    
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->redirectUrl(DOMAIN . '/login');
      return;
    }

    $this->render('pages/perito/form.twig', [
      'titulo' => 'Novo Perito',
      'page' => 'perito',
      'action' => 'criar'
    ]);
  }

  public function editar($params)
  {
    $this->setParams($params);
    $this->requirePermission('perito_editar');
    
    $id = $params['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->redirectUrl(DOMAIN . '/perito');
      return;
    }

    $model = new Perito();
    $perito = $model->getPerito($id, $empresa);
    
    if (!$perito->getResult()) {
      $this->redirectUrl(DOMAIN . '/perito');
      return;
    }

    $this->render('pages/perito/form.twig', [
      'titulo' => 'Editar Perito',
      'page' => 'perito',
      'action' => 'editar',
      'perito' => $perito->getResult()[0] ?? null
    ]);
  }

  public function visualizar($params)
  {
    $this->setParams($params);
    $this->requirePermission('perito_ver');
    
    $id = $params['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->redirectUrl(DOMAIN . '/perito');
      return;
    }

    $model = new Perito();
    $perito = $model->getPerito($id, $empresa);
    
    if (!$perito->getResult()) {
      $this->redirectUrl(DOMAIN . '/perito');
      return;
    }

    $peritoData = $perito->getResult()[0];

    $this->render('pages/perito/visualizar.twig', [
      'titulo' => 'Detalhes do Perito',
      'page' => 'perito',
      'perito' => $peritoData
    ]);
  }

  public function criarSalvar($params)
  {
    $this->setParams($params);
    $this->requirePermission('perito_criar');
    
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Empresa não encontrada']);
      return;
    }

    // Valida dados obrigatórios
    $nome = $_POST['nome'] ?? '';
    
    if (empty($nome)) {
      $this->responseJson(['success' => false, 'message' => 'Nome é obrigatório']);
      return;
    }

    // Processa documento (CPF ou CNPJ)
    $documento = $_POST['documento'] ?? null;
    $tipoDocumento = $_POST['tipo_documento'] ?? 'CPF';

    // Prepara dados para inserção
    $data = [
      'empresa' => $empresa,
      'nome' => $nome,
      'email' => $_POST['email'] ?? null,
      'telefone' => $_POST['telefone'] ?? null,
      'tipo_documento' => $tipoDocumento,
      'documento' => $documento,
      'especialidade' => $_POST['especialidade'] ?? null,
      'registro_profissional' => $_POST['registro_profissional'] ?? null,
      'tipo_registro' => $_POST['tipo_registro'] ?? null,
      'status' => $_POST['status'] ?? 'Ativo',
      'observacoes' => $_POST['observacoes'] ?? null
    ];

    // Cria o perito
    $model = new Perito();
    $result = $model->criarPerito($data);
    
    if ($result->getResult()) {
      $idPerito = (int) $result->getResult();
      $this->enviarNotificacaoEmailPerito($empresa, $idPerito, $data, 'criar');
      $this->responseJson(['success' => true, 'message' => 'Perito criado com sucesso!']);
    } else {
      $this->responseJson(['success' => false, 'message' => 'Erro ao criar perito']);
    }
  }

  public function editarSalvar($params)
  {
    $this->setParams($params);
    $this->requirePermission('perito_editar');
    
    $id = $_POST['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
      return;
    }

    // Valida dados obrigatórios
    $nome = $_POST['nome'] ?? '';
    
    if (empty($nome)) {
      $this->responseJson(['success' => false, 'message' => 'Nome é obrigatório']);
      return;
    }

    // Verifica se o perito existe
    $model = new Perito();
    $perito = $model->getPerito($id, $empresa);
    
    if (!$perito->getResult()) {
      $this->responseJson(['success' => false, 'message' => 'Perito não encontrado']);
      return;
    }

    // Processa documento
    $documento = $_POST['documento'] ?? null;
    $tipoDocumento = $_POST['tipo_documento'] ?? 'CPF';

    // Prepara dados para atualização
    $data = [
      'nome' => $nome,
      'email' => $_POST['email'] ?? null,
      'telefone' => $_POST['telefone'] ?? null,
      'tipo_documento' => $tipoDocumento,
      'documento' => $documento,
      'especialidade' => $_POST['especialidade'] ?? null,
      'registro_profissional' => $_POST['registro_profissional'] ?? null,
      'tipo_registro' => $_POST['tipo_registro'] ?? null,
      'status' => $_POST['status'] ?? 'Ativo',
      'observacoes' => $_POST['observacoes'] ?? null
    ];

    // Atualiza o perito
    $result = $model->atualizarPerito($id, $data, $empresa);
    
    if ($result->getResult()) {
      $this->enviarNotificacaoEmailPerito($empresa, (int)$id, $data, 'editar');
      $this->responseJson(['success' => true, 'message' => 'Perito atualizado com sucesso!']);
    } else {
      $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar perito']);
    }
  }

  public function remover($params)
  {
    $this->setParams($params);
    $this->requirePermission('perito_deletar');
    
    $id = $_POST['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
      return;
    }

    // Verifica se o perito existe
    $model = new Perito();
    $perito = $model->getPerito($id, $empresa);
    
    if (!$perito->getResult()) {
      $this->responseJson(['success' => false, 'message' => 'Perito não encontrado']);
      return;
    }

    // Remove o perito
    $result = $model->removerPerito($id, $empresa);
    
    if ($result->getResult()) {
      $this->responseJson(['success' => true, 'message' => 'Perito removido com sucesso!']);
    } else {
      $this->responseJson(['success' => false, 'message' => 'Erro ao remover perito']);
    }
  }

  /**
   * Endpoint AJAX para DataTables - Retorna dados paginados
   */
  public function datatable($params)
  {
    $this->setParams($params);
    $this->requirePermission('perito_ver');
    
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

    // Extrai parâmetros do DataTables
    $dtParams = DataTableResponse::getParams();
    
    // Filtros adicionais do formulário
    $filtros = [];
    if (isset($_GET['status']) && !empty($_GET['status'])) {
      $filtros['status'] = $_GET['status'];
    }
    if (isset($_GET['especialidade']) && !empty($_GET['especialidade'])) {
      $filtros['especialidade'] = $_GET['especialidade'];
    }

    // Busca dados
    $model = new Perito();
    $result = $model->getPeritosDataTable($empresa, $dtParams, $filtros);
    
    // Formata dados para o DataTables
    $formattedData = [];
    foreach ($result['data'] as $perito) {
      $formattedData[] = [
        // Coluna 0: Nome
        $this->formatNomeCell($perito),
        // Coluna 1: Email
        $this->formatEmailCell($perito),
        // Coluna 2: Telefone
        $this->formatTelefoneCell($perito),
        // Coluna 3: Especialidade
        $this->formatEspecialidadeCell($perito),
        // Coluna 4: Registro Profissional
        $this->formatRegistroCell($perito),
        // Coluna 5: Status
        $this->formatStatusCell($perito),
        // Coluna 6: Ações
        $this->formatAcoesCell($perito)
      ];
    }

    // Retorna resposta formatada
    $response = DataTableResponse::format(
      $formattedData,
      $result['total'],
      $result['filtered'],
      $dtParams['draw']
    );

    $this->responseJson($response);
  }

  /**
   * Formata célula de Nome
   */
  private function formatNomeCell($perito): string
  {
    $html = '<div>';
    $html .= '<h6 class="mb-0">' . htmlspecialchars($perito['nome']) . '</h6>';
    if (!empty($perito['documento'])) {
      $html .= '<small class="opacity-75">' . htmlspecialchars($perito['documento']) . '</small>';
    }
    $html .= '</div>';
    return $html;
  }

  /**
   * Formata célula de Email
   */
  private function formatEmailCell($perito): string
  {
    if (!empty($perito['email'])) {
      return '<a href="mailto:' . htmlspecialchars($perito['email']) . '" class="text-primary">' . htmlspecialchars($perito['email']) . '</a>';
    }
    return '<span class="opacity-50">-</span>';
  }

  /**
   * Formata célula de Telefone
   */
  private function formatTelefoneCell($perito): string
  {
    if (!empty($perito['telefone'])) {
      return '<a href="tel:' . htmlspecialchars($perito['telefone']) . '" class="text-success">' . htmlspecialchars($perito['telefone']) . '</a>';
    }
    return '<span class="opacity-50">-</span>';
  }

  /**
   * Formata célula de Especialidade
   */
  private function formatEspecialidadeCell($perito): string
  {
    if (!empty($perito['especialidade'])) {
      return '<span class="badge bg-info">' . htmlspecialchars($perito['especialidade']) . '</span>';
    }
    return '<span class="opacity-50">-</span>';
  }

  /**
   * Formata célula de Registro Profissional
   */
  private function formatRegistroCell($perito): string
  {
    if (!empty($perito['registro_profissional'])) {
      $html = '<div>';
      $html .= '<strong>' . htmlspecialchars($perito['registro_profissional']) . '</strong>';
      if (!empty($perito['tipo_registro'])) {
        $html .= '<br><small>' . htmlspecialchars($perito['tipo_registro']) . '</small>';
      }
      $html .= '</div>';
      return $html;
    }
    return '<span class="opacity-50">-</span>';
  }

  /**
   * Formata célula de Status
   */
  private function formatStatusCell($perito): string
  {
    $status = $perito['status'];
    $badgeClass = $status === 'Ativo' ? 'bg-success' : 'bg-danger';
    
    return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($status) . '</span>';
  }

  /**
   * Formata célula de Ações
   */
  private function formatAcoesCell($perito): string
  {
    $html = '<div class="d-flex">';
    
    // Botão Visualizar
    $html .= '<a href="' . DOMAIN . '/perito/view/' . $perito['id'] . '" ';
    $html .= 'class="btn btn-info shadow btn-xs sharp me-1" ';
    $html .= 'data-bs-toggle="tooltip" data-bs-title="Visualizar">';
    $html .= '<i class="fa fa-eye"></i></a>';
    
    // Botão Editar (se tiver permissão)
    if ($this->hasPermission('perito_editar')) {
      $html .= '<a href="' . DOMAIN . '/perito/edit/' . $perito['id'] . '" ';
      $html .= 'class="btn btn-success shadow btn-xs sharp me-1" ';
      $html .= 'data-bs-toggle="tooltip" data-bs-title="Editar">';
      $html .= '<i class="fa fa-pencil"></i></a>';
    }
    
    // Botão Remover (se tiver permissão)
    if ($this->hasPermission('perito_deletar')) {
      $html .= '<button type="button" ';
      $html .= 'class="btn btn-danger shadow btn-xs sharp" ';
      $html .= 'onclick="removerPerito(' . $perito['id'] . ', \'' . htmlspecialchars(addslashes($perito['nome'])) . '\')" ';
      $html .= 'data-bs-toggle="tooltip" data-bs-title="Remover">';
      $html .= '<i class="fa fa-trash"></i></button>';
    }
    
    $html .= '</div>';
    return $html;
  }

  /**
   * Verifica se tem permissão
   */
  private function hasPermission(string $permission): bool
  {
    $permissionService = new \Agencia\Close\Services\Login\PermissionsService();
    return $permissionService->verifyPermissions($permission);
  }

  private function enviarNotificacaoEmailPerito(int $empresa, int $idPerito, array $dadosPerito, string $acao): void
  {
    try {
      $dados = [
        'titulo' => $acao === 'criar' ? 'Novo Perito Cadastrado' : 'Perito Atualizado',
        'modulo' => 'Perito',
        'acao' => $acao === 'criar' ? 'Criado' : 'Editado',
        'detalhes' => sprintf(
          '<p><strong>Nome:</strong> %s</p><p><strong>Email:</strong> %s</p><p><strong>Telefone:</strong> %s</p><p><strong>Especialidade:</strong> %s</p><p><strong>Status:</strong> %s</p>',
          htmlspecialchars($dadosPerito['nome'] ?? 'N/A'),
          htmlspecialchars($dadosPerito['email'] ?? 'N/A'),
          htmlspecialchars($dadosPerito['telefone'] ?? 'N/A'),
          htmlspecialchars($dadosPerito['especialidade'] ?? 'N/A'),
          htmlspecialchars($dadosPerito['status'] ?? 'Ativo')
        ),
        'mensagem' => sprintf(
          'Perito %s foi %s.',
          htmlspecialchars($dadosPerito['nome'] ?? ''),
          $acao === 'criar' ? 'cadastrado' : 'atualizado'
        ),
        'url' => DOMAIN . '/perito/view/' . $idPerito
      ];

      $emailService = new EmailNotificationService();
      $tipo = $acao === 'criar' ? 'perito_criar' : 'perito_editar';
      $permissoes = ['perito_ver', 'perito_editar'];
      
      $emailService->criarNotificacaoEEmail(
        $tipo,
        'perito',
        $acao,
        $permissoes,
        $dados,
        $empresa,
        $idPerito,
        true
      );
    } catch (\Exception $e) {
      error_log('Erro ao enviar notificação por e-mail: ' . $e->getMessage());
    }
  }
}

