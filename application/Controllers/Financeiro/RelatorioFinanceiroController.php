<?php

namespace Agencia\Close\Controllers\Financeiro;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Financeiro\ContaReceber;
use Agencia\Close\Models\Financeiro\Faturamento;
use Agencia\Close\Models\Financeiro\PagamentoRecebimento;

class RelatorioFinanceiroController extends Controller
{
    public function index($params)
    {
        $this->setParams($params);
        $this->requirePermission('relatorio_financeiro_ver');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        // Busca dados para o período atual (mês atual)
        $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $dataFim = $_GET['data_fim'] ?? date('Y-m-t');

        $contaReceberModel = new ContaReceber();
        $faturamentoModel = new Faturamento();
        $pagamentoRecebimentoModel = new PagamentoRecebimento();

        // Totais de contas a receber
        $totaisContasReceber = $contaReceberModel->getTotaisFinanceiros($empresa);
        
        // Totais de recebimentos e pagamentos
        $totaisFinanceiros = $pagamentoRecebimentoModel->getTotaisFinanceiros($empresa, $dataInicio, $dataFim);

        // Estatísticas por status
        $estatisticasContas = $contaReceberModel->contarPorStatus($empresa);
        $estatisticasFaturas = $faturamentoModel->contarPorStatus($empresa);

        $this->render('pages/financeiro/relatorios/index.twig', [
            'titulo' => 'Relatórios Financeiros',
            'page' => 'relatorio_financeiro',
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'totais_contas_receber' => $totaisContasReceber,
            'totais_financeiros' => $totaisFinanceiros,
            'estatisticas_contas' => $estatisticasContas->getResult() ?? [],
            'estatisticas_faturas' => $estatisticasFaturas->getResult() ?? []
        ]);
    }

    public function exportar($params)
    {
        $this->setParams($params);
        $this->requirePermission('relatorio_financeiro_exportar');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $tipo = $_GET['tipo'] ?? 'pdf'; // pdf, excel, csv
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $dataFim = $_GET['data_fim'] ?? date('Y-m-t');

        // Aqui você pode implementar a lógica de exportação
        // Por enquanto, apenas retorna JSON com os dados
        $contaReceberModel = new ContaReceber();
        $faturamentoModel = new Faturamento();
        $pagamentoRecebimentoModel = new PagamentoRecebimento();

        $contasReceber = $contaReceberModel->getContasReceber($empresa, [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim
        ]);

        $faturas = $faturamentoModel->getFaturas($empresa, [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim
        ]);

        $recebimentos = $pagamentoRecebimentoModel->getRecebimentos($empresa, [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim
        ]);

        $pagamentos = $pagamentoRecebimentoModel->getPagamentos($empresa, [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim
        ]);

        $dados = [
            'periodo' => [
                'inicio' => $dataInicio,
                'fim' => $dataFim
            ],
            'contas_receber' => $contasReceber->getResult() ?? [],
            'faturas' => $faturas->getResult() ?? [],
            'recebimentos' => $recebimentos->getResult() ?? [],
            'pagamentos' => $pagamentos->getResult() ?? []
        ];

        if ($tipo === 'json') {
            $this->responseJson($dados);
        } else {
            // Aqui você pode implementar geração de PDF, Excel, CSV
            // Por enquanto, retorna JSON
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="relatorio_financeiro_' . date('Y-m-d') . '.json"');
            $this->responseJson($dados);
        }
    }
}

