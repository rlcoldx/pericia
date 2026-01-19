<?php

namespace Agencia\Close\Controllers\Home;

use Agencia\Close\Models\Home\Home;
use Agencia\Close\Models\Home\EstatisticasHome;
use Agencia\Close\Models\User\User;
use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Tarefa\Tarefa;
use Agencia\Close\Helpers\DataTableResponse;

class HomeController extends Controller
{	
  public $today = false;

  public function index()
  {
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

    if (!$empresa) {
      $this->redirectUrl(DOMAIN . '/login');
      return;
    }

    $dataInicio = $_GET['data_inicio'] ?? '';
    $dataFim = $_GET['data_fim'] ?? '';

    // Se não houver filtro, usar mês atual
    if (empty($dataInicio) && empty($dataFim)) {
      $dataInicio = date('Y-m-01'); // Primeiro dia do mês
      $dataFim = date('Y-m-t'); // Último dia do mês
    }

    $estatisticasModel = new EstatisticasHome();

    $this->render('pages/home/home.twig', [
      'page' => 'home', 
      'titulo' => 'Página Inicial',
      'data_inicio' => $dataInicio,
      'data_fim' => $dataFim,
      'estatisticas' => [
        'quesitos' => $estatisticasModel->getEstatisticasQuesitos((int)$empresa, $dataInicio, $dataFim),
        'manifestacoes' => $estatisticasModel->getEstatisticasManifestacoes((int)$empresa, $dataInicio, $dataFim),
        'pareceres' => $estatisticasModel->getEstatisticasPareceres((int)$empresa, $dataInicio, $dataFim),
        'agendamentos' => $estatisticasModel->getEstatisticasAgendamentos((int)$empresa, $dataInicio, $dataFim),
        'financeiro' => $estatisticasModel->getEstatisticasFinanceiro((int)$empresa, $dataInicio, $dataFim),
      ]
    ]);
  }

  public function getEstatisticasAjax()
  {
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

    if (!$empresa) {
      $this->responseJson(['success' => false, 'message' => 'Sessão expirada.']);
      return;
    }

    $dataInicio = $_GET['data_inicio'] ?? '';
    $dataFim = $_GET['data_fim'] ?? '';

    if (empty($dataInicio) && empty($dataFim)) {
      $dataInicio = date('Y-m-01');
      $dataFim = date('Y-m-t');
    }

    $estatisticasModel = new EstatisticasHome();

    $this->responseJson([
      'success' => true,
      'data' => [
        'quesitos' => $estatisticasModel->getEstatisticasQuesitos((int)$empresa, $dataInicio, $dataFim),
        'manifestacoes' => $estatisticasModel->getEstatisticasManifestacoes((int)$empresa, $dataInicio, $dataFim),
        'pareceres' => $estatisticasModel->getEstatisticasPareceres((int)$empresa, $dataInicio, $dataFim),
        'agendamentos' => $estatisticasModel->getEstatisticasAgendamentos((int)$empresa, $dataInicio, $dataFim),
        'financeiro' => $estatisticasModel->getEstatisticasFinanceiro((int)$empresa, $dataInicio, $dataFim),
      ]
    ]);
  }

  /**
   * Endpoint AJAX para DataTable de Tarefas do usuário logado
   */
  public function tarefasDatatable($params)
  {
    try {
      // Limpar qualquer output buffer antes de enviar JSON
      while (ob_get_level() > 0) {
        ob_end_clean();
      }
      
      // Definir header JSON
      header('Content-Type: application/json; charset=utf-8');
      
      $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
      $usuarioId = $_SESSION['pericia_perfil_id'] ?? null;

      if (!$empresa || !$usuarioId) {
        echo json_encode([
          'draw' => (int) ($_GET['draw'] ?? 1),
          'recordsTotal' => 0,
          'recordsFiltered' => 0,
          'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
      }

      $dtParams = DataTableResponse::getParams();

      $filtros = [];
      if (!empty($_GET['status'])) {
        $filtros['status'] = $_GET['status'];
      }

      $tarefaModel = new Tarefa();
      $result = $tarefaModel->getTarefasUsuarioDataTable((int) $usuarioId, (int) $empresa, $dtParams, $filtros);

    $formattedData = [];
    
    // Garantir que result['data'] é um array
    $tarefas = is_array($result['data']) ? $result['data'] : [];
    
    foreach ($tarefas as $tarefa) {
      // Verificar se a tarefa tem os campos necessários
      if (!isset($tarefa['modulo'])) {
        continue;
      }
      
      $moduloNome = ucfirst($tarefa['modulo']);
      if ($tarefa['modulo'] === 'manifestacao') {
        // Verificar se é favorável ou desfavorável para mostrar o nome correto
        $favoravel = $tarefa['favoravel'] ?? null;
        if ($favoravel === 'Favorável') {
          $moduloNome = 'Manifestação';
        } elseif ($favoravel === 'Desfavorável') {
          $moduloNome = 'Impugnação';
        } else {
          // Se não tiver definido ou for outro valor, manter o padrão
          $moduloNome = 'Manifestação/Impugnação';
        }
      }

      $status = isset($tarefa['concluido']) && $tarefa['concluido'] ? 'Concluída' : 'Pendente';
      $statusBadge = isset($tarefa['concluido']) && $tarefa['concluido']
        ? '<span class="badge bg-success">Concluída</span>'
        : '<span class="badge bg-warning">Pendente</span>';

      $dataConclusao = '-';
      if (!empty($tarefa['data_conclusao']) && $tarefa['data_conclusao'] !== '0000-00-00' && $tarefa['data_conclusao'] !== null) {
        try {
          $dataConclusao = date('d/m/Y', strtotime($tarefa['data_conclusao']));
          if ($dataConclusao === false) {
            $dataConclusao = '-';
          }
        } catch (\Exception $e) {
          $dataConclusao = '-';
        }
      }

      $dataCreate = '-';
      if (!empty($tarefa['data_create']) && $tarefa['data_create'] !== '0000-00-00 00:00:00' && $tarefa['data_create'] !== null) {
        try {
          $dataCreate = date('d/m/Y H:i', strtotime($tarefa['data_create']));
          if ($dataCreate === false) {
            $dataCreate = '-';
          }
        } catch (\Exception $e) {
          $dataCreate = '-';
        }
      }

      $reclamada = htmlspecialchars($tarefa['reclamada'] ?? 'Sem Reclamada', ENT_QUOTES, 'UTF-8');

      // URL para editar o registro baseado no módulo
      $urlEditar = '';
      $registroId = (int) ($tarefa['registro_id'] ?? 0);
      
      switch ($tarefa['modulo']) {
        case 'quesito':
          $urlEditar = DOMAIN . '/quesitos/editar/' . $registroId;
          break;
        case 'manifestacao':
          $urlEditar = DOMAIN . '/manifestacoes-impugnacoes/editar/' . $registroId;
          break;
        case 'parecer':
          $urlEditar = DOMAIN . '/pareceres/editar/' . $registroId;
          break;
        case 'agendamento':
          $urlEditar = DOMAIN . '/agendamento/edit/' . $registroId;
          break;
      }

      $acoes = '';
      if ($urlEditar && $registroId > 0) {
        $acoes = '<a href="' . $urlEditar . '" class="btn btn-sm btn-primary py-1" data-bs-toggle="tooltip" title="Visualizar/Editar">
                    <i class="fa fa-eye"></i>
                  </a>';
      }

      $formattedData[] = [
        $moduloNome,
        $reclamada,
        $statusBadge,
        $dataConclusao,
        $dataCreate,
        $acoes,
      ];
    }

      $response = DataTableResponse::format(
        $formattedData,
        (int) $result['total'],
        (int) $result['filtered'],
        (int) $dtParams['draw']
      );

      echo json_encode($response, JSON_UNESCAPED_UNICODE);
      exit;
    } catch (\Exception $e) {
      // Em caso de erro, retornar JSON válido
      while (ob_get_level() > 0) {
        ob_end_clean();
      }
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'draw' => (int) ($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Erro ao carregar tarefas: ' . $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }
}