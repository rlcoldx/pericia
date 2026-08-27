<?php

namespace Agencia\Close\Controllers\Financeiro;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Financeiro\ContaReceber;
use Agencia\Close\Models\Agendamento\Agendamento;
use Agencia\Close\Helpers\DataTableResponse;
use Agencia\Close\Services\Processo\ProcessoVinculoService;

class ContaReceberController extends Controller
{
    public function index($params)
    {
        $this->setParams($params);
        $this->requirePermission('contas_receber_ver');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $model = new ContaReceber();
        $estatisticas = $model->contarPorStatus($empresa);
        $stats = [];
        foreach ($estatisticas->getResult() as $stat) {
            $stats[$stat['status']] = $stat['total'];
        }

        $totais = $model->getTotaisFinanceiros($empresa);

        $this->render('pages/financeiro/contas_receber/index.twig', [
            'titulo' => 'Contas a Receber',
            'page' => 'contas_receber',
            'estatisticas' => $stats,
            'totais' => $totais
        ]);
    }

    public function estatisticas($params)
    {
        $this->setParams($params);
        $this->requirePermission('contas_receber_ver');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $model = new ContaReceber();
        
        // Busca dados de fluxo de caixa (mês atual ou período filtrado)
        $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $dataFim = $_GET['data_fim'] ?? date('Y-m-t');
        $fluxoCaixa = $model->getFluxoCaixa($empresa, $dataInicio, $dataFim);
        
        // Busca dados de inadimplência
        $inadimplencia = $model->getInadimplencia($empresa);

        $this->render('pages/financeiro/contas_receber/estatisticas.twig', [
            'titulo' => 'Estatísticas - Contas a Receber',
            'page' => 'contas_receber_estatisticas',
            'fluxo_caixa' => $fluxoCaixa,
            'inadimplencia' => $inadimplencia,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim
        ]);
    }

    public function criar($params)
    {
        $this->setParams($params);
        $this->requirePermission('contas_receber_criar');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        // Busca agendamentos para vincular (apenas realizados ou agendados)
        $agendamentoModel = new Agendamento();
        $agendamentos = $agendamentoModel->getAgendamentos($empresa, [
            'status' => '' // Busca todos para permitir vincular qualquer agendamento
        ]);
        $processoVinculo = new ProcessoVinculoService();

        $this->render('pages/financeiro/contas_receber/form.twig', [
            'titulo' => 'Nova Conta a Receber',
            'page' => 'contas_receber',
            'action' => 'criar',
            'agendamentos' => $agendamentos->getResult() ?? [],
            'numeros_processo' => $processoVinculo->listarNumerosProcessoDistintos((int) $empresa),
        ]);
    }

    public function editar($params)
    {
        $this->setParams($params);
        $this->requirePermission('contas_receber_editar');
        
        $id = $params['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$id || !$empresa) {
            $this->redirectUrl(DOMAIN . '/contas-receber');
            return;
        }

        $model = new ContaReceber();
        $conta = $model->getContaReceber($id, $empresa);
        
        if (!$conta->getResult()) {
            $this->redirectUrl(DOMAIN . '/contas-receber');
            return;
        }

        $agendamentoModel = new Agendamento();
        $agendamentos = $agendamentoModel->getAgendamentos($empresa);
        $processoVinculo = new ProcessoVinculoService();

        $this->render('pages/financeiro/contas_receber/form.twig', [
            'titulo' => 'Editar Conta a Receber',
            'page' => 'contas_receber',
            'action' => 'editar',
            'conta' => $conta->getResult()[0] ?? null,
            'agendamentos' => $agendamentos->getResult() ?? [],
            'numeros_processo' => $processoVinculo->listarNumerosProcessoDistintos((int) $empresa),
        ]);
    }

    public function visualizar($params)
    {
        $this->setParams($params);
        $this->requirePermission('contas_receber_ver');
        
        $id = $params['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$id || !$empresa) {
            $this->redirectUrl(DOMAIN . '/contas-receber');
            return;
        }

        $model = new ContaReceber();
        $conta = $model->getContaReceber($id, $empresa);
        
        if (!$conta->getResult()) {
            $this->redirectUrl(DOMAIN . '/contas-receber');
            return;
        }

        $this->render('pages/financeiro/contas_receber/visualizar.twig', [
            'titulo' => 'Detalhes da Conta a Receber',
            'page' => 'contas_receber',
            'conta' => $conta->getResult()[0]
        ]);
    }

    public function criarSalvar($params)
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->setParams($params);

            if (!$this->temPermissaoJson('contas_receber_criar')) {
                return;
            }

            $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;

            if (!$empresa) {
                $this->responseJson(['success' => false, 'message' => 'Empresa não encontrada. Faça login novamente.']);
                return;
            }

            $descricao = trim((string) ($_POST['descricao'] ?? ''));
            $clienteNome = trim((string) ($_POST['cliente_nome'] ?? ''));
            $dataVencimento = $this->normalizarDataPost($_POST['data_vencimento'] ?? null);

            if ($descricao === '' || $clienteNome === '' || $dataVencimento === null) {
                $this->responseJson([
                    'success' => false,
                    'message' => 'Preencha Descrição, Cliente e Data de Vencimento (obrigatórios).'
                ]);
                return;
            }

            $valorTotal = $this->parseCurrency($_POST['valor_total'] ?? '0');
            if ($valorTotal <= 0) {
                $this->responseJson(['success' => false, 'message' => 'Informe um Valor Total maior que zero.']);
                return;
            }

            $tipo = $this->normalizarTipoContaPost($_POST['tipo'] ?? null) ?? 'Perícia';
            $tipoPericia = $this->normalizarTipoPericiaPost($_POST['tipo_pericia'] ?? null);

            $valorRecebido = $this->parseCurrency($_POST['valor_recebido'] ?? '0');
            $valorAssistenteRaw = $this->normalizarTextoPost($_POST['valor_assistente'] ?? null);

            $data = [
                'empresa' => (int) $empresa,
                'agendamento_id' => !empty($_POST['agendamento_id']) ? (int) $_POST['agendamento_id'] : null,
                'descricao' => $descricao,
                'cliente_nome' => $clienteNome,
                'cliente_documento' => $this->normalizarTextoPost($_POST['cliente_documento'] ?? null),
                'local_pericia' => $this->normalizarTextoPost($_POST['local_pericia'] ?? null),
                'reclamante_nome' => $this->normalizarTextoPost($_POST['reclamante_nome'] ?? null),
                'numero_processo' => $this->normalizarTextoPost($_POST['numero_processo'] ?? null),
                'valor_total' => $valorTotal,
                'valor_recebido' => $valorRecebido,
                'data_vencimento' => $dataVencimento,
                'data_emissao' => $this->normalizarDataPost($_POST['data_emissao'] ?? null) ?? date('Y-m-d'),
                'data_pericia' => $this->normalizarDataPost($_POST['data_pericia'] ?? null),
                'data_envio' => $this->normalizarDataPost($_POST['data_envio'] ?? null),
                'tipo' => $tipo,
                'tipo_pericia' => $tipoPericia,
                'etapa' => $this->normalizarTextoPost($_POST['etapa'] ?? null) ?? 'PERICIA',
                'situacao' => $this->normalizarTextoPost($_POST['situacao'] ?? null),
                'data_situacao' => $this->normalizarDataPost($_POST['data_situacao'] ?? null),
                'numero_nota_fiscal' => $this->normalizarTextoPost($_POST['numero_nota_fiscal'] ?? null),
                'numero_pedido_cliente' => $this->normalizarTextoPost($_POST['numero_pedido_cliente'] ?? null),
                'numero_boleto' => $this->normalizarTextoPost($_POST['numero_boleto'] ?? null),
                'assistente_nome' => $this->normalizarTextoPost($_POST['assistente_nome'] ?? null),
                'valor_assistente' => $valorAssistenteRaw !== null ? $this->parseCurrency($valorAssistenteRaw) : null,
                'observacoes' => $this->normalizarTextoPost($_POST['observacoes'] ?? null),
            ];

            $model = new ContaReceber();
            $result = $model->criar($data);

            if ($result) {
                $this->responseJson(['success' => true, 'message' => 'Conta a receber criada com sucesso']);
                return;
            }

            $driverMsg = $model->getLastCreateError() ?? '';
            $this->responseJson([
                'success' => false,
                'message' => $driverMsg !== ''
                    ? ('Erro ao criar conta a receber: ' . $driverMsg)
                    : 'Erro ao criar conta a receber. Verifique os dados e tente novamente.',
            ]);
        } catch (\Throwable $e) {
            $this->responseJson([
                'success' => false,
                'message' => 'Erro ao criar conta a receber: ' . $e->getMessage(),
            ]);
        }
    }

    public function editarSalvar($params)
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->setParams($params);

        if (!$this->temPermissaoJson('contas_receber_editar')) {
            return;
        }
        
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$id || !$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $clienteNome = trim((string) ($_POST['cliente_nome'] ?? ''));
        $dataVencimento = $this->normalizarDataPost($_POST['data_vencimento'] ?? null);

        if ($descricao === '' || $clienteNome === '' || $dataVencimento === null) {
            $this->responseJson([
                'success' => false,
                'message' => 'Preencha Descrição, Cliente e Data de Vencimento (obrigatórios).'
            ]);
            return;
        }

        $valorTotal = $this->parseCurrency($_POST['valor_total'] ?? '0');
        if ($valorTotal <= 0) {
            $this->responseJson(['success' => false, 'message' => 'Informe um Valor Total maior que zero.']);
            return;
        }

        $tipo = $this->normalizarTipoContaPost($_POST['tipo'] ?? null) ?? 'Perícia';
        $tipoPericia = $this->normalizarTipoPericiaPost($_POST['tipo_pericia'] ?? null);

        $valorRecebido = $this->parseCurrency($_POST['valor_recebido'] ?? '0');
        $valorAssistenteRaw = $this->normalizarTextoPost($_POST['valor_assistente'] ?? null);

        $data = [
            'agendamento_id' => !empty($_POST['agendamento_id']) ? (int) $_POST['agendamento_id'] : null,
            'descricao' => $descricao,
            'cliente_nome' => $clienteNome,
            'cliente_documento' => $this->normalizarTextoPost($_POST['cliente_documento'] ?? null),
            'local_pericia' => $this->normalizarTextoPost($_POST['local_pericia'] ?? null),
            'reclamante_nome' => $this->normalizarTextoPost($_POST['reclamante_nome'] ?? null),
            'numero_processo' => $this->normalizarTextoPost($_POST['numero_processo'] ?? null),
            'valor_total' => $valorTotal,
            'valor_recebido' => $valorRecebido,
            'data_vencimento' => $dataVencimento,
            'data_emissao' => $this->normalizarDataPost($_POST['data_emissao'] ?? null),
            'data_pericia' => $this->normalizarDataPost($_POST['data_pericia'] ?? null),
            'data_envio' => $this->normalizarDataPost($_POST['data_envio'] ?? null),
            'tipo' => $tipo,
            'tipo_pericia' => $tipoPericia,
            'etapa' => $this->normalizarTextoPost($_POST['etapa'] ?? null) ?? 'PERICIA',
            'situacao' => $this->normalizarTextoPost($_POST['situacao'] ?? null),
            'data_situacao' => $this->normalizarDataPost($_POST['data_situacao'] ?? null),
            'numero_nota_fiscal' => $this->normalizarTextoPost($_POST['numero_nota_fiscal'] ?? null),
            'numero_pedido_cliente' => $this->normalizarTextoPost($_POST['numero_pedido_cliente'] ?? null),
            'numero_boleto' => $this->normalizarTextoPost($_POST['numero_boleto'] ?? null),
            'assistente_nome' => $this->normalizarTextoPost($_POST['assistente_nome'] ?? null),
            'valor_assistente' => $valorAssistenteRaw !== null ? $this->parseCurrency($valorAssistenteRaw) : null,
            'observacoes' => $this->normalizarTextoPost($_POST['observacoes'] ?? null),
        ];

        $model = new ContaReceber();
        $result = $model->atualizar($id, (int) $empresa, $data);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Conta a receber atualizada com sucesso']);
            return;
        }

        $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar conta a receber.']);
    }

    public function remover($params)
    {
        $this->setParams($params);
        $this->requirePermission('contas_receber_deletar');
        
        $id = $_POST['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$id || !$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $model = new ContaReceber();
        $result = $model->remover($id, $empresa);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Conta a receber removida com sucesso']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao remover conta a receber']);
        }
    }

    public function datatable($params)
    {
        $this->setParams($params);
        $this->requirePermission('contas_receber_ver');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->responseJson(DataTableResponse::format([], 0, 0, (int) ($_GET['draw'] ?? 1)));
            return;
        }

        $paramsDataTable = DataTableResponse::getParams();
        $filtros = [];

        // Filtros adicionais
        if (!empty($_GET['status'])) {
            $filtros['status'] = $_GET['status'];
        }
        if (!empty($_GET['situacao'])) {
            $filtros['situacao'] = $_GET['situacao'];
        }
        if (!empty($_GET['data_inicio'])) {
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }
        if (!empty($_GET['data_fim'])) {
            $filtros['data_fim'] = $_GET['data_fim'];
        }
        if (!empty($_GET['numero_processo'])) {
            $filtros['numero_processo'] = $_GET['numero_processo'];
        }
        if (!empty($paramsDataTable['search'])) {
            $filtros['cliente'] = $paramsDataTable['search'];
        }

        $model = new ContaReceber();
        $todasContas = $model->getContasReceberDataTable($empresa, $filtros);
        
        // Aplica paginação manualmente (simplificado)
        $total = count($todasContas);
        $dadosPaginados = array_slice($todasContas, $paramsDataTable['start'], $paramsDataTable['length']);

        // Formata dados para DataTables
        $data = [];
        foreach ($dadosPaginados as $conta) {
            $dataPericia = $conta['data_pericia_completo'] ?? null;
            $dataEnvio = $conta['data_envio'] ?? ($conta['data_envio_financeiro'] ?? null);
            $dataVenc = $conta['data_vencimento'] ?? null;
            $valorAssistente = $conta['valor_assistente_completo'] ?? null;

            $data[] = [
                'id' => $conta['id'],
                'local' => $conta['local_pericia_completo'] ?? '-',
                'reclamante' => $conta['reclamante_nome_completo'] ?? '-',
                'tipo' => $conta['tipo'] ?? '-',
                'etapa' => $conta['etapa'] ?? 'PERICIA',
                'valor' => 'R$ ' . number_format((float) ($conta['valor_total'] ?? 0), 2, ',', '.'),
                'processo' => $conta['numero_processo_completo'] ?? '-',
                'data_pericia' => $dataPericia ? date('d/m/Y', strtotime($dataPericia)) : '-',
                'situacao' => $conta['situacao'] ?? '-',
                'numero_pedido' => $conta['numero_pedido_export']
                    ?? ($conta['numero_pedido_cliente'] ?? '-'),
                'numero_nota_fiscal' => $conta['numero_nota_fiscal'] ?? '-',
                'numero_boleto' => $conta['numero_boleto'] ?? '-',
                'data_envio' => $dataEnvio ? date('d/m/Y', strtotime($dataEnvio)) : '-',
                'prazo' => $dataVenc ? date('d/m/Y', strtotime($dataVenc)) : '-',
                'status' => $conta['status_pagamento_agendamento'] ?? ($conta['status'] ?? '-'),
                'assistente' => $conta['assistente_nome_completo'] ?? '-',
                'valor_assistente' => $valorAssistente !== null && $valorAssistente !== ''
                    ? ('R$ ' . number_format((float) $valorAssistente, 2, ',', '.'))
                    : '-',
                'acoes' => $this->formatAcoesCell($conta)
            ];
        }

        $this->responseJson(DataTableResponse::format($data, $total, $total, $paramsDataTable['draw']));
    }

    /**
     * Formata célula de ações
     */
    private function formatAcoesCell($conta): string
    {
        $html = '<div class="d-flex gap-1">';
        
        $html .= '<a href="' . DOMAIN . '/contas-receber/view/' . $conta['id'] . '" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="Visualizar">';
        $html .= '<i class="fa-light fa-eye"></i>';
        $html .= '</a>';
        
        $permissionService = new \Agencia\Close\Services\Login\PermissionsService();
        
        if ($permissionService->verifyPermissions('contas_receber_editar')) {
            $html .= '<a href="' . DOMAIN . '/contas-receber/edit/' . $conta['id'] . '" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Editar">';
            $html .= '<i class="fa-light fa-pencil"></i>';
            $html .= '</a>';
        }
        
        if ($permissionService->verifyPermissions('contas_receber_deletar')) {
            $html .= '<button type="button" class="btn btn-danger btn-sm btn-remover-conta" data-id="' . $conta['id'] . '" data-descricao="' . htmlspecialchars($conta['descricao']) . '" data-bs-toggle="tooltip" title="Remover">';
            $html .= '<i class="fa-light fa-trash"></i>';
            $html .= '</button>';
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Converte valor monetário de string para float
     */
    private function parseCurrency($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }
        
        $value = preg_replace('/[^0-9,.]/', '', (string) $value);
        $value = str_replace(',', '.', str_replace('.', '', $value));
        
        return (float) $value;
    }

    private function normalizarTextoPost($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }
        $texto = trim((string) $value);

        return $texto !== '' ? $texto : null;
    }

    /**
     * Tipo da conta: Perícia / Serviço / Outro.
     */
    private function normalizarTipoContaPost($value): ?string
    {
        $texto = $this->normalizarTextoPost($value);
        if ($texto === null) {
            return null;
        }

        $validos = ['Perícia', 'Serviço', 'Outro'];
        if (in_array($texto, $validos, true)) {
            return $texto;
        }

        return null;
    }

    /**
     * Tipo de perícia (opcional).
     */
    private function normalizarTipoPericiaPost($value): ?string
    {
        $texto = $this->normalizarTextoPost($value);
        if ($texto === null) {
            return null;
        }

        $validos = [
            'Perícia Médica',
            'Técnica',
            'Ergonomia',
            'Fisiologia',
            'Quesitos',
            'Não Realizada',
            'Outros',
        ];
        if (in_array($texto, $validos, true)) {
            return $texto;
        }

        $codigo = mb_strtoupper($texto, 'UTF-8');
        $mapa = [
            'MEDICA' => 'Perícia Médica',
            'MÉDICA' => 'Perícia Médica',
            'MEDIACA' => 'Perícia Médica',
            'TECNICA' => 'Técnica',
            'TÉCNICA' => 'Técnica',
            'ERGONO' => 'Ergonomia',
            'ERGONOMIA' => 'Ergonomia',
            'CINESIO' => 'Fisiologia',
            'FISIOLOGIA' => 'Fisiologia',
            'QUESITOS' => 'Quesitos',
            'QUESITO' => 'Quesitos',
            'NAO REALIZADA' => 'Não Realizada',
            'NÃO REALIZADA' => 'Não Realizada',
            'OUTROS' => 'Outros',
            'OUTRO' => 'Outros',
        ];

        return $mapa[$codigo] ?? null;
    }

    private function normalizarDataPost($value): ?string
    {
        $texto = $this->normalizarTextoPost($value);
        if ($texto === null) {
            return null;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $texto, $m)) {
            return sprintf('%s-%s-%s', $m[3], $m[2], $m[1]);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
            return $texto;
        }

        $ts = strtotime($texto);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    public function exportarExcel($params)
    {
        $this->setParams($params);
        $this->requirePermission('contas_receber_ver');

        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $filtros = [];
        if (!empty($_GET['status'])) {
            $filtros['status'] = $_GET['status'];
        }
        if (!empty($_GET['situacao'])) {
            $filtros['situacao'] = $_GET['situacao'];
        }
        if (!empty($_GET['data_inicio'])) {
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }
        if (!empty($_GET['data_fim'])) {
            $filtros['data_fim'] = $_GET['data_fim'];
        }
        if (!empty($_GET['numero_processo'])) {
            $filtros['numero_processo'] = $_GET['numero_processo'];
        }
        if (!empty($_GET['search'])) {
            $filtros['cliente'] = $_GET['search'];
        }

        $model = new ContaReceber();
        $contas = $model->getContasReceberDataTable((int) $empresa, $filtros);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Contas a Receber');

        $headers = [
            'A1' => 'LOCAL',
            'B1' => 'RECLAMANTE',
            'C1' => 'TIPO',
            'D1' => 'PROCESSO',
            'E1' => 'ETAPA',
            'F1' => 'VALOR',
            'G1' => 'DATA DA PERÍCIA',
            'H1' => 'NUMERO DO PEDIDO',
        ];
        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '7030A0'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($contas as $conta) {
            $localRaw = $conta['local_pericia_completo'] ?? ($conta['local_pericia'] ?? '');
            $tipo = $this->formatarTipoExportacao($conta);
            $etapa = trim((string) ($conta['etapa'] ?? 'PERICIA'));
            if (strcasecmp($etapa, 'PERICIA') === 0) {
                $etapa = 'PERÍCIA';
            }
            $dataPericia = $conta['data_pericia_completo'] ?? ($conta['data_pericia'] ?? null);
            $valor = (float) ($conta['valor_total'] ?? 0);
            $pedido = $conta['numero_pedido_export']
                ?? ($conta['numero_pedido_cliente'] ?? '');

            $sheet->setCellValue('A' . $row, $this->extrairCidadeEstado((string) $localRaw));
            $sheet->setCellValue('B' . $row, (string) ($conta['reclamante_nome_completo'] ?? ($conta['reclamante_nome'] ?? '')));
            $sheet->setCellValue('C' . $row, $tipo);
            $sheet->setCellValueExplicit(
                'D' . $row,
                (string) ($conta['numero_processo_completo'] ?? ($conta['numero_processo'] ?? '')),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $sheet->setCellValue('E' . $row, mb_strtoupper($etapa, 'UTF-8'));
            $sheet->setCellValue('F' . $row, 'R$ ' . number_format($valor, 2, ',', '.'));
            $sheet->setCellValue(
                'G' . $row,
                $dataPericia ? date('d/m/Y', strtotime((string) $dataPericia)) : ''
            );
            $sheet->setCellValueExplicit(
                'H' . $row,
                (string) $pedido,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C6EFCE'],
                ],
            ]);
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'contas_receber_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Extrai CIDADE/UF do endereço completo.
     * Aceita: JOINVILLE/SC, Joinville-SC, CAMPINAS SP, "... , Campinas - SP"
     */
    private function extrairCidadeEstado(string $local): string
    {
        $local = trim(preg_replace('/\s+/u', ' ', $local) ?? '');
        if ($local === '' || $local === '-') {
            return '';
        }

        $ufs = [
            'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG',
            'PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO',
        ];
        $ufsAlt = implode('|', $ufs);

        // Já no formato CIDADE/UF
        if (preg_match('/^([A-Za-zÀ-ÿ][A-Za-zÀ-ÿ\s\.\']+)\s*\/\s*(' . $ufsAlt . ')$/iu', $local, $m)) {
            return $this->formatarCidadeUf($m[1], $m[2]);
        }

        // Final do texto: Cidade[-/ ]UF  (ex.: Joinville-SC, CAMPINAS SP, Campinas / SP)
        if (preg_match(
            '/(?:^|[,\s])([A-Za-zÀ-ÿ][A-Za-zÀ-ÿ\s\.\']{1,40}?)\s*[-–\/]?\s*(' . $ufsAlt . ')\s*$/iu',
            $local,
            $m
        )) {
            return $this->formatarCidadeUf($m[1], $m[2]);
        }

        // Último segmento após vírgula
        $parts = preg_split('/,\s*/u', $local) ?: [];
        $last = trim((string) end($parts));
        if ($last !== '' && preg_match(
            '/^([A-Za-zÀ-ÿ][A-Za-zÀ-ÿ\s\.\']+?)\s*[-–\/]?\s*(' . $ufsAlt . ')\s*$/iu',
            $last,
            $m
        )) {
            return $this->formatarCidadeUf($m[1], $m[2]);
        }

        // Dois últimos tokens: "CIDADE UF"
        if (preg_match('/\b([A-Za-zÀ-ÿ][A-Za-zÀ-ÿ\.\']+(?:\s+[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ\.\']+){0,3})\s+(' . $ufsAlt . ')\s*$/iu', $local, $m)) {
            $cidade = trim($m[1]);
            // Evita capturar "SALA 15" etc. — cidade não deve ser só número/sigla curta
            if (mb_strlen($cidade, 'UTF-8') >= 3) {
                return $this->formatarCidadeUf($cidade, $m[2]);
            }
        }

        return mb_strtoupper($local, 'UTF-8');
    }

    private function formatarCidadeUf(string $cidade, string $uf): string
    {
        $cidade = trim($cidade);
        // Remove prefixos comuns grudados no final do endereço
        $cidade = preg_replace('/^(?:JD\.?|JARDIM|BAIRRO|CENTRO)\s+/iu', '', $cidade) ?? $cidade;
        $cidade = trim($cidade, " \t\n\r\0\x0B,.-/");

        return mb_strtoupper($cidade, 'UTF-8') . '/' . strtoupper(trim($uf));
    }

    private function formatarTipoExportacao(array $conta): string
    {
        $tipo = trim((string) (
            $conta['tipo_pericia']
            ?? $conta['agendamento_tipo_pericia']
            ?? $conta['tipo']
            ?? ''
        ));
        if ($tipo === '') {
            return '';
        }

        $codigo = mb_strtoupper($tipo, 'UTF-8');
        $mapa = [
            'MEDICA' => 'MÉDICA',
            'MÉDICA' => 'MÉDICA',
            'MEDIACA' => 'MÉDICA',
            'PERÍCIA MÉDICA' => 'MÉDICA',
            'PERICIA MEDICA' => 'MÉDICA',
            'TECNICA' => 'TÉCNICA',
            'TÉCNICA' => 'TÉCNICA',
            'ERGONO' => 'ERGONOMIA',
            'ERGONOMIA' => 'ERGONOMIA',
            'CINESIO' => 'FISIOLOGIA',
            'FISIOLOGIA' => 'FISIOLOGIA',
            'QUESITOS' => 'QUESITOS',
            'NÃO REALIZADA' => 'NÃO REALIZADA',
            'NAO REALIZADA' => 'NÃO REALIZADA',
            'OUTROS' => 'OUTROS',
            'OUTRO' => 'OUTROS',
        ];

        return $mapa[$codigo] ?? $mapa[$tipo] ?? mb_strtoupper($tipo, 'UTF-8');
    }

    private function temPermissaoJson(string $permission): bool
    {
        $permissionService = new \Agencia\Close\Services\Login\PermissionsService();
        if ($permissionService->verifyPermissions($permission)) {
            return true;
        }

        $this->responseJson([
            'success' => false,
            'message' => 'Você não tem permissão para esta operação.',
        ]);

        return false;
    }
}

