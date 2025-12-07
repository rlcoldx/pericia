<?php

namespace Agencia\Close\Controllers\Agendamento;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Agendamento\Agendamento;
use Agencia\Close\Models\Equipe\Equipe;
use Agencia\Close\Models\User\User;
use Agencia\Close\Models\Perito\Perito;
use Agencia\Close\Services\Notificacao\EmailNotificationService;
use Agencia\Close\Helpers\DataTableResponse;

class AgendamentoController extends Controller
{	
  public function index($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_ver');
    
    // Busca a empresa logada
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->redirectUrl(DOMAIN . '/login');
      return;
    }

    // Filtros
    $filtros = [];
    if (isset($_GET['status']) && !empty($_GET['status'])) {
      $filtros['status'] = $_GET['status'];
    }
    if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
      $filtros['data_inicio'] = $_GET['data_inicio'];
    }
    if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
      $filtros['data_fim'] = $_GET['data_fim'];
    }
    if (isset($_GET['perito_id']) && !empty($_GET['perito_id'])) {
      $filtros['perito_id'] = $_GET['perito_id'];
    }

    // Lista os agendamentos
    $model = new Agendamento();
    $agendamentos = $model->getAgendamentos($empresa, $filtros);
    
    // Busca estatísticas
    $estatisticas = $model->contarPorStatus($empresa);
    $stats = [];
    foreach ($estatisticas->getResult() as $stat) {
      $stats[$stat['status']] = $stat['total'];
    }

    // Busca peritos para filtro
    $equipeModel = new Equipe();
    $peritos = $equipeModel->getEquipe($empresa);
    
    $this->render('pages/agendamento/index.twig', [
      'titulo' => 'Agendamentos de Perícias',
      'page' => 'agendamento',
      'agendamentos' => $agendamentos->getResult() ?? [],
      'estatisticas' => $stats,
      'peritos' => $peritos->getResult() ?? [],
      'filtros' => $filtros
    ]);
  }

  public function criar($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_criar');
    
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->redirectUrl(DOMAIN . '/login');
      return;
    }

    // Busca peritos disponíveis
    $peritoModel = new Perito();
    $peritos = $peritoModel->getPeritosAtivos($empresa);
    
    $this->render('pages/agendamento/form.twig', [
      'titulo' => 'Novo Agendamento',
      'page' => 'agendamento',
      'action' => 'criar',
      'peritos' => $peritos->getResult() ?? [],
      'agendamento' => null
    ]);
  }

  public function editar($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_editar');
    
    $id = $params['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->redirectUrl(DOMAIN . '/agendamento');
      return;
    }

    // Busca o agendamento
    $model = new Agendamento();
    $agendamento = $model->getAgendamento($id, $empresa);
    
    if (!$agendamento->getResult()) {
      $this->redirectUrl(DOMAIN . '/agendamento');
      return;
    }

    // Busca peritos disponíveis
    $peritoModel = new Perito();
    $peritos = $peritoModel->getPeritosAtivos($empresa);
    
    $this->render('pages/agendamento/form.twig', [
      'titulo' => 'Editar Agendamento',
      'page' => 'agendamento',
      'action' => 'editar',
      'agendamento' => $agendamento->getResult()[0] ?? null,
      'peritos' => $peritos->getResult() ?? []
    ]);
  }

  public function visualizar($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_ver');
    
    $id = $params['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->redirectUrl(DOMAIN . '/agendamento');
      return;
    }

    // Busca o agendamento
    $model = new Agendamento();
    $agendamento = $model->getAgendamento($id, $empresa);
    
    if (!$agendamento->getResult()) {
      $this->redirectUrl(DOMAIN . '/agendamento');
      return;
    }

    $agendamentoData = $agendamento->getResult()[0];
    
    // Busca dados do perito se existir
    $peritoData = null;
    if ($agendamentoData['perito_id']) {
      $userModel = new User();
      $perito = $userModel->getUserByID($agendamentoData['perito_id']);
      $peritoData = $perito->getResult()[0] ?? null;
    }

    // Busca dados do aprovador se existir
    $aprovadorData = null;
    if ($agendamentoData['aprovado_por']) {
      $userModel = new User();
      $aprovador = $userModel->getUserByID($agendamentoData['aprovado_por']);
      $aprovadorData = $aprovador->getResult()[0] ?? null;
    }
    
    $this->render('pages/agendamento/visualizar.twig', [
      'titulo' => 'Detalhes do Agendamento',
      'page' => 'agendamento',
      'agendamento' => $agendamentoData,
      'perito' => $peritoData,
      'aprovador' => $aprovadorData
    ]);
  }

  public function criarSalvar($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_criar');
    
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Empresa não encontrada']);
      return;
    }

    // Valida dados obrigatórios
    $clienteNome = $_POST['cliente_nome'] ?? '';
    $dataAgendamento = $_POST['data_agendamento'] ?? '';
    $horaAgendamento = $_POST['hora_agendamento'] ?? '';
    $peritoId = $_POST['perito_id'] ?? null;

    if (empty($clienteNome) || empty($dataAgendamento) || empty($horaAgendamento)) {
      $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
      return;
    }

    // Verifica conflito de horário se perito foi informado
    if ($peritoId) {
      $model = new Agendamento();
      $conflito = $model->verificarConflitoHorario($peritoId, $dataAgendamento, $horaAgendamento);
      
      if ($conflito->getResult()) {
        $this->responseJson(['success' => false, 'message' => 'Já existe um agendamento para este perito no mesmo horário']);
        return;
      }
    }

    // Prepara dados para inserção - CLOVIS
    // Status pode ser definido no cadastro; se não vier, assume "Pendente"
    $status = $_POST['status'] ?? 'Pendente';
    $statusValidos = ['Pendente', 'Agendado', 'Realizado', 'Cancelado'];
    if (!in_array($status, $statusValidos, true)) {
      $status = 'Pendente';
    }

    $data = [
      'empresa' => $empresa,
      'data_entrada' => !empty($_POST['data_entrada']) ? $_POST['data_entrada'] : null,
      'cliente_nome' => $clienteNome,
      'cliente_email' => $_POST['cliente_email'] ?? null,
      'cliente_telefone' => $_POST['cliente_telefone'] ?? null,
      'cliente_cpf' => $_POST['cliente_cpf'] ?? $_POST['cliente_documento'] ?? null,
      'numero_processo' => $_POST['numero_processo'] ?? null,
      'vara' => $_POST['vara'] ?? null,
      'reclamante_nome' => $_POST['reclamante_nome'] ?? null,
      'valor_pericia_cobrado' => !empty($_POST['valor_pericia_cobrado']) ? $this->parseCurrency($_POST['valor_pericia_cobrado']) : null,
      'tipo_pericia' => $_POST['tipo_pericia'] ?? null,
      'numero_tipo_pericia' => $_POST['numero_tipo_pericia'] ?? null,
      'data_agendamento' => $dataAgendamento,
      'hora_agendamento' => $horaAgendamento,
      'perito_id' => $peritoId ?: null,
      'assistente_nome' => $_POST['assistente_nome'] ?? null,
      'valor_pago_assistente' => !empty($_POST['valor_pago_assistente']) ? $this->parseCurrency($_POST['valor_pago_assistente']) : null,
      'local_pericia' => $_POST['local_pericia'] ?? null,
      'status' => $status,
      'observacoes' => $_POST['observacoes'] ?? null,
      // MARCELO
      'data_realizada' => $_POST['data_realizada'] ?? null,
      'data_fatal' => $_POST['data_fatal'] ?? null,
      'data_entrega_parecer' => $_POST['data_entrega_parecer'] ?? null,
      'status_parecer' => $_POST['status_parecer'] ?? null,
      'obs_parecer' => $_POST['obs_parecer'] ?? null,
      // MAURO
      'data_pagamento_assistente' => $_POST['data_pagamento_assistente'] ?? null,
      'numero_pedido_cliente' => $_POST['numero_pedido_cliente'] ?? null,
      'numero_nota_fiscal' => $_POST['numero_nota_fiscal'] ?? null,
      'numero_boleto' => $_POST['numero_boleto'] ?? null,
      'data_envio_financeiro' => $_POST['data_envio_financeiro'] ?? null,
      'data_vencimento_financeiro' => $_POST['data_vencimento_financeiro'] ?? null,
      'status_pagamento' => $_POST['status_pagamento'] ?? null
    ];

    // Cria o agendamento
    $model = new Agendamento();
    $result = $model->criarAgendamento($data);
    
    if ($result->getResult()) {
      $idAgendamento = (int) $result->getResult();
      $this->enviarNotificacaoEmailAgendamento($empresa, $idAgendamento, $data, 'criar');
      $this->responseJson(['success' => true, 'message' => 'Agendamento criado com sucesso']);
    } else {
      $this->responseJson(['success' => false, 'message' => 'Erro ao criar agendamento']);
    }
  }

  public function editarSalvar($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_editar');
    
    $id = $_POST['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
      return;
    }

    // Valida dados obrigatórios
    $clienteNome = $_POST['cliente_nome'] ?? '';
    $dataAgendamento = $_POST['data_agendamento'] ?? '';
    $horaAgendamento = $_POST['hora_agendamento'] ?? '';
    $peritoId = $_POST['perito_id'] ?? null;

    if (empty($clienteNome) || empty($dataAgendamento) || empty($horaAgendamento)) {
      $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
      return;
    }

    // Verifica conflito de horário se perito foi informado
    if ($peritoId) {
      $model = new Agendamento();
      $conflito = $model->verificarConflitoHorario($peritoId, $dataAgendamento, $horaAgendamento, $id);
      
      if ($conflito->getResult()) {
        $this->responseJson(['success' => false, 'message' => 'Já existe um agendamento para este perito no mesmo horário']);
        return;
      }
    }

    // Prepara dados para atualização - Todos os campos
    // Converte strings vazias para null
    $data = [
      // CLOVIS
      'data_entrada' => !empty($_POST['data_entrada']) ? $_POST['data_entrada'] : null,
      'cliente_nome' => $clienteNome,
      'cliente_email' => isset($_POST['cliente_email']) && $_POST['cliente_email'] !== '' ? $_POST['cliente_email'] : null,
      'cliente_telefone' => isset($_POST['cliente_telefone']) && $_POST['cliente_telefone'] !== '' ? $_POST['cliente_telefone'] : null,
      'cliente_cpf' => isset($_POST['cliente_cpf']) && $_POST['cliente_cpf'] !== '' ? $_POST['cliente_cpf'] : (isset($_POST['cliente_documento']) && $_POST['cliente_documento'] !== '' ? $_POST['cliente_documento'] : null),
      'numero_processo' => isset($_POST['numero_processo']) && $_POST['numero_processo'] !== '' ? $_POST['numero_processo'] : null,
      'vara' => isset($_POST['vara']) && $_POST['vara'] !== '' ? $_POST['vara'] : null,
      'reclamante_nome' => isset($_POST['reclamante_nome']) && $_POST['reclamante_nome'] !== '' ? $_POST['reclamante_nome'] : null,
      'valor_pericia_cobrado' => isset($_POST['valor_pericia_cobrado']) && $_POST['valor_pericia_cobrado'] !== '' ? $this->parseCurrency($_POST['valor_pericia_cobrado']) : null,
      'tipo_pericia' => isset($_POST['tipo_pericia']) && $_POST['tipo_pericia'] !== '' ? $_POST['tipo_pericia'] : null,
      'numero_tipo_pericia' => isset($_POST['numero_tipo_pericia']) && $_POST['numero_tipo_pericia'] !== '' ? $_POST['numero_tipo_pericia'] : null,
      'data_agendamento' => $dataAgendamento,
      'hora_agendamento' => $horaAgendamento,
      'perito_id' => !empty($peritoId) ? $peritoId : null,
      'assistente_nome' => isset($_POST['assistente_nome']) && $_POST['assistente_nome'] !== '' ? $_POST['assistente_nome'] : null,
      'valor_pago_assistente' => isset($_POST['valor_pago_assistente']) && $_POST['valor_pago_assistente'] !== '' ? $this->parseCurrency($_POST['valor_pago_assistente']) : null,
      'local_pericia' => isset($_POST['local_pericia']) && $_POST['local_pericia'] !== '' ? $_POST['local_pericia'] : null,
      'status' => isset($_POST['status']) && $_POST['status'] !== '' ? $_POST['status'] : null,
      'observacoes' => isset($_POST['observacoes']) && $_POST['observacoes'] !== '' ? $_POST['observacoes'] : null,
      // MARCELO
      'data_realizada' => isset($_POST['data_realizada']) && $_POST['data_realizada'] !== '' ? $_POST['data_realizada'] : null,
      'data_fatal' => isset($_POST['data_fatal']) && $_POST['data_fatal'] !== '' ? $_POST['data_fatal'] : null,
      'data_entrega_parecer' => isset($_POST['data_entrega_parecer']) && $_POST['data_entrega_parecer'] !== '' ? $_POST['data_entrega_parecer'] : null,
      'status_parecer' => isset($_POST['status_parecer']) && $_POST['status_parecer'] !== '' ? $_POST['status_parecer'] : null,
      'obs_parecer' => isset($_POST['obs_parecer']) && $_POST['obs_parecer'] !== '' ? $_POST['obs_parecer'] : null,
      // MAURO
      'data_pagamento_assistente' => isset($_POST['data_pagamento_assistente']) && $_POST['data_pagamento_assistente'] !== '' ? $_POST['data_pagamento_assistente'] : null,
      'numero_pedido_cliente' => isset($_POST['numero_pedido_cliente']) && $_POST['numero_pedido_cliente'] !== '' ? $_POST['numero_pedido_cliente'] : null,
      'numero_nota_fiscal' => isset($_POST['numero_nota_fiscal']) && $_POST['numero_nota_fiscal'] !== '' ? $_POST['numero_nota_fiscal'] : null,
      'numero_boleto' => isset($_POST['numero_boleto']) && $_POST['numero_boleto'] !== '' ? $_POST['numero_boleto'] : null,
      'data_envio_financeiro' => isset($_POST['data_envio_financeiro']) && $_POST['data_envio_financeiro'] !== '' ? $_POST['data_envio_financeiro'] : null,
      'data_vencimento_financeiro' => isset($_POST['data_vencimento_financeiro']) && $_POST['data_vencimento_financeiro'] !== '' ? $_POST['data_vencimento_financeiro'] : null,
      'status_pagamento' => isset($_POST['status_pagamento']) && $_POST['status_pagamento'] !== '' ? $_POST['status_pagamento'] : null
    ];

    // Atualiza o agendamento
    $model = new Agendamento();
    
    try {
      $result = $model->atualizarAgendamento($id, $data, $empresa);
      
      if ($result->getResult()) {
        $this->enviarNotificacaoEmailAgendamento($empresa, (int)$id, $data, 'editar');
        $this->responseJson(['success' => true, 'message' => 'Agendamento atualizado com sucesso']);
      } else {
        // Tenta obter mais informações sobre o erro
        $errorInfo = method_exists($result, 'getErrorInfo') ? $result->getErrorInfo() : null;
        $errorMessage = 'Erro ao atualizar agendamento';
        
        // Se houver informações de erro, adiciona ao log
        if ($errorInfo) {
          $errorDetails = $errorInfo['driver_message'] ?? $errorInfo['message'] ?? 'Erro desconhecido';
          error_log("Erro ao atualizar agendamento ID {$id}: " . print_r($errorInfo, true));
          
          // Em ambiente de desenvolvimento, pode mostrar mais detalhes
          if (defined('DEBUG') && DEBUG) {
            $errorMessage .= ': ' . $errorDetails;
          }
        }
        
        $this->responseJson(['success' => false, 'message' => $errorMessage]);
      }
    } catch (\Exception $e) {
      error_log("Exceção ao atualizar agendamento ID {$id}: " . $e->getMessage());
      $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar agendamento: ' . $e->getMessage()]);
    }
  }

  public function remover($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_deletar');
    
    $id = $_POST['id'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
      return;
    }

    // Verifica se o agendamento existe antes de remover
    $model = new Agendamento();
    $agendamento = $model->getAgendamento($id, $empresa);
    
    if (!$agendamento->getResult()) {
      $this->responseJson(['success' => false, 'message' => 'Agendamento não encontrado']);
      return;
    }

    // Remove o agendamento
    $result = $model->removerAgendamento($id, $empresa);
    
    if ($result->getResult()) {
      $this->responseJson(['success' => true, 'message' => 'Agendamento removido com sucesso']);
    } else {
      $this->responseJson(['success' => false, 'message' => 'Erro ao remover agendamento']);
    }
  }

  public function alterarStatus($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_editar');
    
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$id || !$status || !$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
      return;
    }

    // Valida status
    $statusValidos = ['Pendente', 'Agendado', 'Realizado', 'Cancelado'];
    if (!in_array($status, $statusValidos)) {
      $this->responseJson(['success' => false, 'message' => 'Status inválido']);
      return;
    }

    // Verifica se o agendamento existe
    $model = new Agendamento();
    $agendamento = $model->getAgendamento($id, $empresa);
    
    if (!$agendamento->getResult()) {
      $this->responseJson(['success' => false, 'message' => 'Agendamento não encontrado']);
      return;
    }

    // Atualiza o status
    $data = ['status' => $status];
    $result = $model->atualizarAgendamento($id, $data, $empresa);
    
    if ($result->getResult()) {
      $this->responseJson(['success' => true, 'message' => 'Status atualizado com sucesso']);
    } else {
      $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar status']);
    }
  }

  /**
   * Endpoint AJAX para DataTables - Retorna dados paginados
   */
  public function datatable($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_ver');
    
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
    if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
      $filtros['data_inicio'] = $_GET['data_inicio'];
    }
    if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
      $filtros['data_fim'] = $_GET['data_fim'];
    }
    if (isset($_GET['perito_id']) && !empty($_GET['perito_id'])) {
      $filtros['perito_id'] = $_GET['perito_id'];
    }

    // Busca dados
    $model = new Agendamento();
    $result = $model->getAgendamentosDataTable($empresa, $dtParams, $filtros);
    
    // Formata dados para o DataTables
    $formattedData = [];
    foreach ($result['data'] as $agendamento) {
      // Formata cada linha conforme necessário
      $formattedData[] = [
        // Coluna 0: Cliente
        $this->formatClienteCell($agendamento),
        // Coluna 1: Data/Hora
        $this->formatDataHoraCell($agendamento),
        // Coluna 2: Perito
        $this->formatPeritoCell($agendamento),
        // Coluna 3: Tipo
        $this->formatTipoCell($agendamento),
        // Coluna 4: Status
        $this->formatStatusCell($agendamento),
        // Coluna 5: Local
        $this->formatLocalCell($agendamento),
        // Coluna 6: Ações
        $this->formatAcoesCell($agendamento)
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
   * Formata célula de Cliente
   */
  private function formatClienteCell($agendamento): string
  {
    $html = '<div>';
    $html .= '<h6 class="mb-0">' . htmlspecialchars($agendamento['cliente_nome']) . '</h6>';
    if (!empty($agendamento['cliente_email'])) {
      $html .= '<small><a href="mailto:' . htmlspecialchars($agendamento['cliente_email']) . '" class="text-primary">' . htmlspecialchars($agendamento['cliente_email']) . '</a></small>';
    }
    $html .= '</div>';
    return $html;
  }

  /**
   * Formata célula de Data/Hora
   */
  private function formatDataHoraCell($agendamento): string
  {
    $data = date('d/m/Y', strtotime($agendamento['data_agendamento']));
    $hora = date('H:i', strtotime($agendamento['hora_agendamento']));
    return '<div><strong>' . $data . '</strong><br><small>' . $hora . '</small></div>';
  }

  /**
   * Formata célula de Perito
   */
  private function formatPeritoCell($agendamento): string
  {
    if (!empty($agendamento['perito_id'])) {
      return '<span class="badge bg-info">Perito #' . $agendamento['perito_id'] . '</span>';
    }
    return '<span class="opacity-50">Não definido</span>';
  }

  /**
   * Formata célula de Tipo
   */
  private function formatTipoCell($agendamento): string
  {
    if (!empty($agendamento['tipo_pericia'])) {
      return htmlspecialchars($agendamento['tipo_pericia']);
    }
    return '<span class="opacity-50">-</span>';
  }

  /**
   * Formata célula de Status
   */
  private function formatStatusCell($agendamento): string
  {
    $status = $agendamento['status'];
    $badgeClass = 'bg-secondary';
    
    switch ($status) {
      case 'Pendente':
        $badgeClass = 'bg-warning';
        break;
      case 'Agendado':
        $badgeClass = 'bg-primary';
        break;
      case 'Realizado':
      case 'Aprovado':
        $badgeClass = 'bg-success';
        break;
      case 'Cancelado':
      case 'Rejeitado':
        $badgeClass = 'bg-danger';
        break;
    }
    
    return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($status) . '</span>';
  }

  /**
   * Formata célula de Local
   */
  private function formatLocalCell($agendamento): string
  {
    if (!empty($agendamento['local_pericia'])) {
      return '<small>' . htmlspecialchars($agendamento['local_pericia']) . '</small>';
    }
    return '<span class="opacity-50">-</span>';
  }

  /**
   * Formata célula de Ações
   */
  private function formatAcoesCell($agendamento): string
  {
    $html = '<div class="d-flex">';
    
    // Botão Visualizar
    $html .= '<a href="' . DOMAIN . '/agendamento/view/' . $agendamento['id'] . '" ';
    $html .= 'class="btn btn-info shadow btn-xs sharp me-1" ';
    $html .= 'data-bs-toggle="tooltip" data-bs-title="Visualizar">';
    $html .= '<i class="fa fa-eye"></i></a>';
    
    // Botão Editar (se tiver permissão)
    if ($this->hasPermission('agendamento_editar')) {
      $html .= '<a href="' . DOMAIN . '/agendamento/edit/' . $agendamento['id'] . '" ';
      $html .= 'class="btn btn-success shadow btn-xs sharp me-1" ';
      $html .= 'data-bs-toggle="tooltip" data-bs-title="Editar">';
      $html .= '<i class="fa fa-pencil"></i></a>';
    }
    
    // Botão Remover (se tiver permissão)
    if ($this->hasPermission('agendamento_deletar')) {
      $html .= '<button type="button" ';
      $html .= 'class="btn btn-danger shadow btn-xs sharp" ';
      $html .= 'onclick="removerAgendamento(' . $agendamento['id'] . ', \'' . htmlspecialchars(addslashes($agendamento['cliente_nome'])) . '\')" ';
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

  /**
   * Converte valor monetário brasileiro para formato numérico
   * Ex: "1.234,56" -> 1234.56
   */
  private function parseCurrency(string $value): ?float
  {
    // Remove pontos (separadores de milhar) e substitui vírgula por ponto
    $cleanValue = str_replace(['.', ','], ['', '.'], $value);
    $floatValue = filter_var($cleanValue, FILTER_VALIDATE_FLOAT);
    return $floatValue !== false ? $floatValue : null;
  }

  /**
   * Exibe a página do calendário de agendamentos
   */
  public function calendario($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_ver');
    
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->redirectUrl(DOMAIN . '/login');
      return;
    }

    // Busca peritos para filtro
    $peritoModel = new Perito();
    $peritos = $peritoModel->getPeritosAtivos($empresa);
    
    $this->render('pages/agendamento/calendario.twig', [
      'titulo' => 'Calendário de Agendamentos',
      'page' => 'agendamento',
      'peritos' => $peritos->getResult() ?? []
    ]);
  }

  /**
   * Retorna eventos do calendário em formato JSON (FullCalendar)
   */
  public function calendarioEventos($params)
  {
    $this->setParams($params);
    $this->requirePermission('agendamento_ver');
    
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    
    if (!$empresa) {
      $this->responseJson([]);
      return;
    }

    // Parâmetros do FullCalendar
    $start = $_GET['start'] ?? date('Y-m-d', strtotime('-1 month'));
    $end = $_GET['end'] ?? date('Y-m-d', strtotime('+1 month'));
    
    // Converte formato ISO para Y-m-d
    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));

    // Filtros
    $filtros = [];
    if (isset($_GET['status']) && !empty($_GET['status'])) {
      $filtros['status'] = $_GET['status'];
    }
    if (isset($_GET['perito_id']) && !empty($_GET['perito_id'])) {
      $filtros['perito_id'] = $_GET['perito_id'];
    }

    // Busca agendamentos
    $model = new Agendamento();
    $eventos = $model->getAgendamentosCalendario($empresa, $start, $end, $filtros);
    
    $this->responseJson($eventos);
  }

  private function enviarNotificacaoEmailAgendamento(int $empresa, int $idAgendamento, array $dados, string $acao): void
  {
    try {
      $peritoModel = new Perito();
      $peritoNome = 'N/A';
      
      if (!empty($dados['perito_id'])) {
        $peritoRead = $peritoModel->getPerito($dados['perito_id'], $empresa);
        $peritoNome = $peritoRead->getResult()[0]['nome'] ?? 'N/A';
      }

      $dadosEmail = [
        'titulo' => $acao === 'criar' ? 'Novo Agendamento Criado' : 'Agendamento Atualizado',
        'modulo' => 'Agendamento',
        'acao' => $acao === 'criar' ? 'Criado' : 'Editado',
        'detalhes' => sprintf(
          '<p><strong>Cliente:</strong> %s</p><p><strong>Data Agendamento:</strong> %s</p><p><strong>Hora:</strong> %s</p><p><strong>Perito:</strong> %s</p><p><strong>Status:</strong> %s</p><p><strong>Local:</strong> %s</p>',
          htmlspecialchars($dados['cliente_nome'] ?? 'N/A'),
          $dados['data_agendamento'] ? date('d/m/Y', strtotime($dados['data_agendamento'])) : 'N/A',
          htmlspecialchars($dados['hora_agendamento'] ?? 'N/A'),
          htmlspecialchars($peritoNome),
          htmlspecialchars($dados['status'] ?? 'Pendente'),
          htmlspecialchars($dados['local_pericia'] ?? 'N/A')
        ),
        'mensagem' => sprintf(
          'Agendamento para o cliente %s foi %s.',
          htmlspecialchars($dados['cliente_nome'] ?? ''),
          $acao === 'criar' ? 'criado' : 'atualizado'
        ),
        'url' => DOMAIN . '/agendamento/edit/' . $idAgendamento
      ];

      $emailService = new EmailNotificationService();
      $tipo = $acao === 'criar' ? 'agendamento_criar' : 'agendamento_editar';
      $permissoes = ['agendamento_ver', 'agendamento_editar'];
      
      $emailService->criarNotificacaoEEmail($tipo, 'agendamento', $acao, $permissoes, $dadosEmail, $empresa, $idAgendamento, true);
    } catch (\Exception $e) {
      error_log('Erro ao enviar notificação por e-mail: ' . $e->getMessage());
    }
  }
}

