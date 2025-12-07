<?php

namespace Agencia\Close\Controllers\Home;

use Agencia\Close\Models\Home\Home;
use Agencia\Close\Models\Home\EstatisticasHome;
use Agencia\Close\Models\User\User;
use Agencia\Close\Controllers\Controller;

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
}