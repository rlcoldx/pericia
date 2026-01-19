<?php

namespace Agencia\Close\Controllers\Agendamento;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Agendamento\Agendamento;
use Agencia\Close\Models\Equipe\Equipe;
use Agencia\Close\Models\User\User;
use Agencia\Close\Models\Perito\Perito;
use Agencia\Close\Models\Assistente\Assistente;
use Agencia\Close\Models\Reclamada\Reclamada;
use Agencia\Close\Models\Reclamante\Reclamante;
use Agencia\Close\Models\Parecer\Parecer;
use Agencia\Close\Services\Notificacao\EmailNotificationService;
use Agencia\Close\Helpers\DataTableResponse;
use Agencia\Close\Models\Tarefa\Tarefa;

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
    
    // Busca assistentes disponíveis
    $assistenteModel = new Assistente();
    $assistentes = $assistenteModel->listar((int) $empresa);

    // Busca reclamadas e reclamantes para o card de parecer
    $reclamadaModel = new Reclamada();
    $reclamanteModel = new Reclamante();
    $reclamadas = $reclamadaModel->listar((int) $empresa);
    $reclamantes = $reclamanteModel->listar((int) $empresa);

    // Busca tipos de parecer existentes para o select2
    $parecerModel = new Parecer();
    $tiposParecer = $parecerModel->listarTipos((int) $empresa);
    
    // Busca usuários para tarefas
    $equipeModel = new Equipe();
    
    $this->render('pages/agendamento/form.twig', [
      'titulo' => 'Novo Agendamento',
      'page' => 'agendamento',
      'action' => 'criar',
      'peritos' => $peritos->getResult() ?? [],
      'assistentes' => $assistentes->getResult() ?? [],
      'reclamadas' => $reclamadas->getResult() ?? [],
      'reclamantes' => $reclamantes->getResult() ?? [],
      'tipos_parecer' => $tiposParecer->getResult() ?? [],
      'usuarios' => $equipeModel->getUsuariosAtivos((int) $empresa)->getResult() ?? [],
      'agendamento' => null,
      'parecer_agendamento' => null,
      'tarefa' => null,
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
    
    // Busca assistentes disponíveis
    $assistenteModel = new Assistente();
    $assistentes = $assistenteModel->listar((int) $empresa);

    // Busca reclamadas e reclamantes para o card de parecer
    $reclamadaModel = new Reclamada();
    $reclamanteModel = new Reclamante();
    $reclamadas = $reclamadaModel->listar((int) $empresa);
    $reclamantes = $reclamanteModel->listar((int) $empresa);

    // Busca tipos de parecer existentes para o select2
    $parecerModel = new Parecer();
    $tiposParecer = $parecerModel->listarTipos((int) $empresa);

    // Busca parecer já vinculado ao agendamento (se houver)
    $parecerAgendamento = $parecerModel->getPorAgendamento((int) $id, (int) $empresa)->getResult()[0] ?? null;
    
    // Busca usuários para tarefas
    $equipeModel = new Equipe();
    $tarefaModel = new Tarefa();
    
    // Busca tarefa existente (prioriza tarefa do parecer se existir)
    $tarefa = null;
    if (!empty($parecerAgendamento)) {
      $tarefaRead = $tarefaModel->getPorModuloRegistro('parecer', (int) $parecerAgendamento['id'], (int) $empresa);
      $tarefa = $tarefaRead->getResult()[0] ?? null;
    }
    if (!$tarefa) {
      $tarefaRead = $tarefaModel->getPorModuloRegistro('agendamento', $id, (int) $empresa);
      $tarefa = $tarefaRead->getResult()[0] ?? null;
    }
    
    $this->render('pages/agendamento/form.twig', [
      'titulo' => 'Editar Agendamento',
      'page' => 'agendamento',
      'action' => 'editar',
      'agendamento' => $agendamento->getResult()[0] ?? null,
      'peritos' => $peritos->getResult() ?? [],
      'assistentes' => $assistentes->getResult() ?? [],
      'reclamadas' => $reclamadas->getResult() ?? [],
      'reclamantes' => $reclamantes->getResult() ?? [],
      'tipos_parecer' => $tiposParecer->getResult() ?? [],
      'usuarios' => $equipeModel->getUsuariosAtivos((int) $empresa)->getResult() ?? [],
      'parecer_agendamento' => $parecerAgendamento,
      'tarefa' => $tarefa,
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
    // Definir header JSON no início para evitar corrupção
    header('Content-Type: application/json; charset=utf-8');
    
    $this->setParams($params);
    $this->requirePermission('agendamento_criar');
    
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
    $clienteNome = $_POST['cliente_nome'] ?? '';
    $dataAgendamento = $_POST['data_agendamento'] ?? '';
    $horaAgendamento = $_POST['hora_agendamento'] ?? '';
    $peritoId = $_POST['perito_id'] ?? null;

    if (empty($clienteNome) || empty($dataAgendamento) || empty($horaAgendamento)) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
      return;
    }

    // Verifica conflito de horário se perito foi informado
    if ($peritoId) {
      $model = new Agendamento();
      $conflito = $model->verificarConflitoHorario($peritoId, $dataAgendamento, $horaAgendamento);
      
      if ($conflito->getResult()) {
        // Limpar qualquer output buffer antes de enviar JSON
        if (ob_get_level() > 0) {
          ob_clean();
        }
        $this->responseJson(['success' => false, 'message' => 'Já existe um agendamento para este perito no mesmo horário']);
        return;
      }
    }

    // Prepara dados para inserção - CLOVIS
    // Status pode ser definido no cadastro; se não vier, assume "Agendado"
    $status = $_POST['status'] ?? 'Agendado';
    $statusValidos = ['Agendado', 'Perícia Realizada', 'Não Realizada', 'Parecer para Revisar'];
    if (!in_array($status, $statusValidos, true)) {
      $status = 'Agendado';
    }

    // Valida e normaliza status_parecer para o enum
    $statusParecer = $_POST['parecer_status_parecer'] ?? null;
    if ($statusParecer) {
      $statusParecerValidos = ['OK', 'FAVORAVEL', 'DESFAVORAVEL', 'PARCIAL FAVORAVEL', 'NR (NÃO REALIZADO)'];
      $statusParecerUpper = mb_strtoupper($statusParecer, 'UTF-8');
      if (!in_array($statusParecerUpper, $statusParecerValidos, true)) {
        // Se não estiver no enum válido, tenta mapear
        $statusParecer = null;
      } else {
        $statusParecer = $statusParecerUpper;
      }
    }

    // Função auxiliar para normalizar campos de data (converte string vazia para null)
    $normalizeDate = function($value) {
      return (!empty($value) && trim($value) !== '') ? $value : null;
    };

    // Função auxiliar para normalizar campos de texto (converte string vazia para null)
    $normalizeText = function($value) {
      return (!empty($value) && trim($value) !== '') ? $value : null;
    };

    $data = [
      'empresa' => $empresa,
      'data_entrada' => $normalizeDate($_POST['data_entrada'] ?? null),
      'cliente_nome' => $clienteNome,
      'cliente_email' => $normalizeText($_POST['cliente_email'] ?? null),
      'cliente_telefone' => $normalizeText($_POST['cliente_telefone'] ?? null),
      'cliente_cpf' => $normalizeText($_POST['cliente_cpf'] ?? $_POST['cliente_documento'] ?? null),
      'numero_processo' => $normalizeText($_POST['numero_processo'] ?? null),
      'vara' => $normalizeText($_POST['vara'] ?? null),
      'reclamante_nome' => $normalizeText($_POST['reclamante_nome'] ?? null),
      'valor_pericia_cobrado' => !empty($_POST['valor_pericia_cobrado']) ? $this->parseCurrency($_POST['valor_pericia_cobrado']) : null,
      'tipo_pericia' => $normalizeText($_POST['tipo_pericia'] ?? null),
      'numero_tipo_pericia' => $normalizeText($_POST['numero_tipo_pericia'] ?? null),
      'data_agendamento' => $dataAgendamento,
      'hora_agendamento' => $horaAgendamento,
      'perito_id' => $peritoId ?: null,
      'assistente_nome' => $normalizeText($_POST['assistente_nome'] ?? null),
      'assistente_id' => !empty($_POST['assistente_id']) ? (int) $_POST['assistente_id'] : null,
      'valor_pago_assistente' => !empty($_POST['valor_pago_assistente']) ? $this->parseCurrency($_POST['valor_pago_assistente']) : null,
      'local_pericia' => $normalizeText($_POST['local_pericia'] ?? null),
      'status' => $status,
      'observacoes' => $normalizeText($_POST['observacoes'] ?? null),
      // MARCELO
      'data_realizada' => $normalizeDate($_POST['parecer_data_realizacao'] ?? null),
      'data_fatal' => $normalizeDate($_POST['parecer_data_fatal'] ?? null),
      'data_entrega_parecer' => $normalizeDate($_POST['parecer_data_entrega_parecer'] ?? null),
      'status_parecer' => $statusParecer,
      'obs_parecer' => $normalizeText($_POST['parecer_observacoes'] ?? null),
      // MAURO
      'data_pagamento_assistente' => $normalizeDate($_POST['data_pagamento_assistente'] ?? null),
      'numero_pedido_cliente' => $normalizeText($_POST['numero_pedido_cliente'] ?? null),
      'numero_nota_fiscal' => $normalizeText($_POST['numero_nota_fiscal'] ?? null),
      'numero_boleto' => $normalizeText($_POST['numero_boleto'] ?? null),
      'data_envio_financeiro' => $normalizeDate($_POST['data_envio_financeiro'] ?? null),
      'data_vencimento_financeiro' => $normalizeDate($_POST['data_vencimento_financeiro'] ?? null),
      'status_pagamento' => $normalizeText($_POST['status_pagamento'] ?? null)
    ];

    // Cria o agendamento
    $model = new Agendamento();
    $result = $model->criarAgendamento($data);
    
    if (!$result->getResult()) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Erro ao criar agendamento']);
      return;
    }

    $idAgendamento = (int) $result->getResult();

    // Salva/atualiza parecer vinculado se os campos foram preenchidos
    $parecerId = $this->salvarOuAtualizarParecerDoAgendamento((int) $empresa, $idAgendamento);

    // Salvar tarefa se fornecida (não bloqueia o cadastro se falhar)
    try {
      // Verificar se o checkbox está marcado (pode vir como 'on' ou '1')
      $tarefaConcluido = isset($_POST['tarefa_concluido']) && 
                       ($_POST['tarefa_concluido'] == '1' || $_POST['tarefa_concluido'] == 'on');
      
      // Sempre salvar tarefa se houver qualquer dado relacionado, incluindo apenas o checkbox
      $temDadosTarefa = $tarefaConcluido || !empty($_POST['tarefa_usuario_responsavel_id']) || !empty($_POST['tarefa_data_conclusao']) || !empty($_POST['tarefa_texto']);
      
      if ($temDadosTarefa) {
        $tarefaModel = new Tarefa();
        $moduloTarefa = $parecerId ? 'parecer' : 'agendamento';
        $registroTarefa = $parecerId ?: $idAgendamento;

        // Se o checkbox está marcado, garantir que o usuário responsável seja o usuário logado se não foi definido outro
        $usuarioResponsavelId = null;
        if (!empty($_POST['tarefa_usuario_responsavel_id'])) {
          $usuarioResponsavelId = (int) $_POST['tarefa_usuario_responsavel_id'];
        } elseif ($tarefaConcluido) {
          // Se está marcando como concluído mas não definiu responsável, usar o logado
          $usuarioResponsavelId = $_SESSION['pericia_perfil_id'] ?? null;
        }

        $tarefaModel->salvarTarefa($moduloTarefa, $registroTarefa, (int) $empresa, [
          'concluido' => $tarefaConcluido ? 1 : 0,
          'usuario_responsavel_id' => $usuarioResponsavelId,
          'data_conclusao' => $_POST['tarefa_data_conclusao'] ?? null,
          'tarefa_texto' => $_POST['tarefa_texto'] ?? null,
        ]);
      }
    } catch (\Exception $e) {
      // Não bloqueia o cadastro se a tarefa falhar
    } catch (\Error $e) {
      // Não bloqueia o cadastro se a tarefa falhar
    }

    try {
      $this->enviarNotificacaoEmailAgendamento($empresa, $idAgendamento, $data, 'criar');
    } catch (\Exception $e) {
      // Erro silencioso no envio de email
    }

    // Limpar qualquer output buffer antes de enviar JSON
    if (ob_get_level() > 0) {
      ob_clean();
    }
    
    $this->responseJson(['success' => true, 'message' => 'Agendamento criado com sucesso']);
  }

  public function editarSalvar($params)
  {
    // Definir header JSON no início para evitar corrupção
    header('Content-Type: application/json; charset=utf-8');
    
    $this->setParams($params);
    $this->requirePermission('agendamento_editar');
    
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
    $clienteNome = $_POST['cliente_nome'] ?? '';
    $dataAgendamento = $_POST['data_agendamento'] ?? '';
    $horaAgendamento = $_POST['hora_agendamento'] ?? '';
    $peritoId = $_POST['perito_id'] ?? null;

    if (empty($clienteNome) || empty($dataAgendamento) || empty($horaAgendamento)) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
      return;
    }

    // Verifica conflito de horário se perito foi informado
    if ($peritoId) {
      $model = new Agendamento();
      $conflito = $model->verificarConflitoHorario($peritoId, $dataAgendamento, $horaAgendamento, $id);
      
      if ($conflito->getResult()) {
        // Limpar qualquer output buffer antes de enviar JSON
        if (ob_get_level() > 0) {
          ob_clean();
        }
        $this->responseJson(['success' => false, 'message' => 'Já existe um agendamento para este perito no mesmo horário']);
        return;
      }
    }

    // Prepara dados para atualização - Todos os campos
    // Função auxiliar para normalizar campos de data (converte string vazia para null)
    $normalizeDate = function($value) {
      return (!empty($value) && trim($value) !== '') ? $value : null;
    };

    // Função auxiliar para normalizar campos de texto (converte string vazia para null)
    $normalizeText = function($value) {
      return (!empty($value) && trim($value) !== '') ? $value : null;
    };

    // Valida e normaliza status_parecer para o enum
    $statusParecer = $_POST['parecer_status_parecer'] ?? null;
    if ($statusParecer) {
      $statusParecerValidos = ['OK', 'FAVORAVEL', 'DESFAVORAVEL', 'PARCIAL FAVORAVEL', 'NR (NÃO REALIZADO)'];
      $statusParecerUpper = mb_strtoupper($statusParecer, 'UTF-8');
      if (!in_array($statusParecerUpper, $statusParecerValidos, true)) {
        $statusParecer = null;
      } else {
        $statusParecer = $statusParecerUpper;
      }
    }

    $data = [
      // CLOVIS
      'data_entrada' => $normalizeDate($_POST['data_entrada'] ?? null),
      'cliente_nome' => $clienteNome,
      'cliente_email' => $normalizeText($_POST['cliente_email'] ?? null),
      'cliente_telefone' => $normalizeText($_POST['cliente_telefone'] ?? null),
      'cliente_cpf' => $normalizeText($_POST['cliente_cpf'] ?? $_POST['cliente_documento'] ?? null),
      'numero_processo' => $normalizeText($_POST['numero_processo'] ?? null),
      'vara' => $normalizeText($_POST['vara'] ?? null),
      'reclamante_nome' => $normalizeText($_POST['reclamante_nome'] ?? null),
      'valor_pericia_cobrado' => !empty($_POST['valor_pericia_cobrado']) ? $this->parseCurrency($_POST['valor_pericia_cobrado']) : null,
      'tipo_pericia' => $normalizeText($_POST['tipo_pericia'] ?? null),
      'numero_tipo_pericia' => $normalizeText($_POST['numero_tipo_pericia'] ?? null),
      'data_agendamento' => $dataAgendamento,
      'hora_agendamento' => $horaAgendamento,
      'perito_id' => !empty($peritoId) ? $peritoId : null,
      'assistente_nome' => $normalizeText($_POST['assistente_nome'] ?? null),
      'assistente_id' => !empty($_POST['assistente_id']) ? (int) $_POST['assistente_id'] : null,
      'valor_pago_assistente' => !empty($_POST['valor_pago_assistente']) ? $this->parseCurrency($_POST['valor_pago_assistente']) : null,
      'local_pericia' => $normalizeText($_POST['local_pericia'] ?? null),
      'status' => isset($_POST['status']) && $_POST['status'] !== '' ? $_POST['status'] : null,
      'observacoes' => $normalizeText($_POST['observacoes'] ?? null),
      // MARCELO
      'data_realizada' => $normalizeDate($_POST['parecer_data_realizacao'] ?? null),
      'data_fatal' => $normalizeDate($_POST['parecer_data_fatal'] ?? null),
      'data_entrega_parecer' => $normalizeDate($_POST['parecer_data_entrega_parecer'] ?? null),
      'status_parecer' => $statusParecer,
      'obs_parecer' => $normalizeText($_POST['parecer_observacoes'] ?? null),
      // MAURO
      'data_pagamento_assistente' => $normalizeDate($_POST['data_pagamento_assistente'] ?? null),
      'numero_pedido_cliente' => $normalizeText($_POST['numero_pedido_cliente'] ?? null),
      'numero_nota_fiscal' => $normalizeText($_POST['numero_nota_fiscal'] ?? null),
      'numero_boleto' => $normalizeText($_POST['numero_boleto'] ?? null),
      'data_envio_financeiro' => $normalizeDate($_POST['data_envio_financeiro'] ?? null),
      'data_vencimento_financeiro' => $normalizeDate($_POST['data_vencimento_financeiro'] ?? null),
      'status_pagamento' => $normalizeText($_POST['status_pagamento'] ?? null)
    ];

    // Atualiza o agendamento
    $model = new Agendamento();
    
    try {
      $result = $model->atualizarAgendamento($id, $data, $empresa);
      
      if (!$result->getResult()) {
        // Limpar qualquer output buffer antes de enviar JSON
        if (ob_get_level() > 0) {
          ob_clean();
        }
        $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar agendamento']);
        return;
      }

      // Salva/atualiza parecer vinculado se os campos foram preenchidos
      $parecerId = $this->salvarOuAtualizarParecerDoAgendamento((int) $empresa, (int) $id);

      // Salvar tarefa se fornecida (não bloqueia a atualização se falhar)
      try {
        // Verificar se o checkbox está marcado (pode vir como 'on' ou '1')
        $tarefaConcluido = isset($_POST['tarefa_concluido']) && 
                         ($_POST['tarefa_concluido'] == '1' || $_POST['tarefa_concluido'] == 'on');
        
        // Sempre salvar tarefa se houver qualquer dado relacionado, incluindo apenas o checkbox
        $temDadosTarefa = $tarefaConcluido || !empty($_POST['tarefa_usuario_responsavel_id']) || !empty($_POST['tarefa_data_conclusao']);
        
        if ($temDadosTarefa) {
          $tarefaModel = new Tarefa();
          $moduloTarefa = $parecerId ? 'parecer' : 'agendamento';
          $registroTarefa = $parecerId ?: $id;

          // Buscar tarefa existente para manter o usuário responsável se não foi alterado
          $tarefaExistente = $tarefaModel->getPorModuloRegistro($moduloTarefa, $registroTarefa, (int) $empresa);
          $tarefaData = $tarefaExistente->getResult()[0] ?? null;

          // Se o checkbox está marcado, garantir que o usuário responsável seja mantido ou definido
          $usuarioResponsavelId = null;
          if (!empty($_POST['tarefa_usuario_responsavel_id'])) {
            $usuarioResponsavelId = (int) $_POST['tarefa_usuario_responsavel_id'];
          } elseif ($tarefaData && !empty($tarefaData['usuario_responsavel_id'])) {
            // Manter o responsável atual se não foi alterado
            $usuarioResponsavelId = (int) $tarefaData['usuario_responsavel_id'];
          } elseif ($tarefaConcluido) {
            // Se está marcando como concluído mas não definiu responsável, usar o logado
            $usuarioResponsavelId = $_SESSION['pericia_perfil_id'] ?? null;
          }

          $tarefaModel->salvarTarefa($moduloTarefa, $registroTarefa, (int) $empresa, [
            'concluido' => $tarefaConcluido ? 1 : 0,
            'usuario_responsavel_id' => $usuarioResponsavelId,
            'data_conclusao' => $_POST['tarefa_data_conclusao'] ?? null,
          ]);
        }
      } catch (\Exception $e) {
        // Não bloqueia a atualização se a tarefa falhar
      } catch (\Error $e) {
        // Não bloqueia a atualização se a tarefa falhar
      }

      try {
        $this->enviarNotificacaoEmailAgendamento($empresa, (int)$id, $data, 'editar');
      } catch (\Exception $e) {
        // Erro silencioso no envio de email
      }

      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      
      $this->responseJson(['success' => true, 'message' => 'Agendamento atualizado com sucesso']);
    } catch (\Exception $e) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      
    } catch (\Exception $e) {
      // Limpar qualquer output buffer antes de enviar JSON
      if (ob_get_level() > 0) {
        ob_clean();
      }
      
      $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar agendamento']);
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
    $statusValidos = ['Agendado', 'Perícia Realizada', 'Não Realizada', 'Parecer para Revisar'];
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
    if (!empty($agendamento['perito_nome'])) {
      return '<span class="badge bg-info">' . htmlspecialchars($agendamento['perito_nome']) . '</span>';
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
   * Cria ou atualiza um parecer vinculado ao agendamento, se os campos do card foram preenchidos.
   * Retorna o ID do parecer salvo ou null se não houve criação/atualização.
   */
  private function salvarOuAtualizarParecerDoAgendamento(int $empresa, int $agendamentoId): ?int
  {
    try {
      $dataRealizacao = $_POST['parecer_data_realizacao'] ?? '';
      $tipoParecer = $_POST['parecer_tipo'] ?? '';

      // Se não preencher os campos principais, não cria/atualiza
      if (empty($dataRealizacao) || empty($tipoParecer)) {
        return null;
      }

      $tipoParecer = mb_strtoupper($tipoParecer, 'UTF-8');

      $dadosParecer = [
        'empresa' => $empresa,
        'agendamento_id' => $agendamentoId,
        'data_realizacao' => $dataRealizacao,
        'data_fatal' => $_POST['parecer_data_fatal'] ?? null,
        'data_entrega_parecer' => $_POST['parecer_data_entrega_parecer'] ?? null,
        'status_parecer' => $_POST['parecer_status_parecer'] ?? null,
        'tipo' => $tipoParecer,
        'assistente' => null,
        'assistente_id' => !empty($_POST['parecer_assistente_id']) ? (int) $_POST['parecer_assistente_id'] : null,
        'reclamada_id' => !empty($_POST['parecer_reclamada_id']) ? (int) $_POST['parecer_reclamada_id'] : null,
        'reclamante_id' => !empty($_POST['parecer_reclamante_id']) ? (int) $_POST['parecer_reclamante_id'] : null,
        'funcoes' => isset($_POST['parecer_funcoes']) && $_POST['parecer_funcoes'] !== '' ? $_POST['parecer_funcoes'] : null,
        'observacoes' => isset($_POST['parecer_observacoes']) && $_POST['parecer_observacoes'] !== '' ? $_POST['parecer_observacoes'] : null,
      ];

      $parecerModel = new Parecer();
      $parecerExistente = $parecerModel->getPorAgendamento($agendamentoId, $empresa)->getResult()[0] ?? null;

      if ($parecerExistente) {
        $parecerModel->atualizar((int) $parecerExistente['id'], $empresa, $dadosParecer);
        return (int) $parecerExistente['id'];
      }

      $result = $parecerModel->criar($dadosParecer);
      return $result->getResult() ? (int) $result->getResult() : null;
    } catch (\Exception $e) {
      return null;
    } catch (\Error $e) {
      return null;
    }
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
          htmlspecialchars($dados['status'] ?? 'Agendado'),
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

