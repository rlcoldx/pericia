<?php

namespace Agencia\Close\Controllers\Processo;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Services\Processo\ProcessoVinculoService;

class VinculoProcessoController extends Controller
{
    /**
     * GET /vinculo-processo/buscar?numero=...&exclude_fonte=quesito&exclude_id=12
     * exclude_fonte: agendamento | quesito | manifestacao | parecer
     */
    public function buscar($params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->setParams($params);

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        if (!$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        $numero = isset($_GET['numero']) ? trim((string) $_GET['numero']) : '';
        if ($numero === '') {
            $this->responseJson(['success' => true, 'found' => false]);
            return;
        }

        $excludeFonte = isset($_GET['exclude_fonte']) ? trim((string) $_GET['exclude_fonte']) : null;
        $excludeId = isset($_GET['exclude_id']) ? (int) $_GET['exclude_id'] : null;

        $permitidas = ['agendamento', 'quesito', 'manifestacao', 'parecer', ''];
        if ($excludeFonte !== null && $excludeFonte !== '' && !in_array($excludeFonte, ['agendamento', 'quesito', 'manifestacao', 'parecer'], true)) {
            $excludeFonte = null;
            $excludeId = null;
        }
        if ($excludeFonte === '' || $excludeFonte === null) {
            $excludeFonte = null;
            $excludeId = null;
        }
        if ($excludeId !== null && $excludeId <= 0) {
            $excludeId = null;
            $excludeFonte = null;
        }

        $service = new ProcessoVinculoService();
        $dados = $service->buscarPorNumero((int) $empresa, $numero, $excludeFonte, $excludeId);

        if ($dados === null) {
            $this->responseJson(['success' => true, 'found' => false]);
            return;
        }

        $this->responseJson([
            'success' => true,
            'found' => true,
            'dados' => $dados,
        ]);
    }
}
