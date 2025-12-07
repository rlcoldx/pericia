<?php

namespace Agencia\Close\Controllers\Financeiro;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Financeiro\PagamentoRecebimento;
use Agencia\Close\Models\Financeiro\ContaReceber;
use Agencia\Close\Models\Financeiro\Faturamento;
use Agencia\Close\Models\Agendamento\Agendamento;
use Agencia\Close\Helpers\DataTableResponse;

class PagamentoRecebimentoController extends Controller
{
    public function recebimentos($params)
    {
        $this->setParams($params);
        $this->requirePermission('pagamento_recebimento_ver');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $model = new PagamentoRecebimento();
        $totais = $model->getTotaisFinanceiros($empresa);

        $this->render('pages/financeiro/pagamentos_recebimentos/recebimentos.twig', [
            'titulo' => 'Recebimentos',
            'page' => 'recebimentos',
            'totais' => $totais
        ]);
    }

    public function pagamentos($params)
    {
        $this->setParams($params);
        $this->requirePermission('pagamento_recebimento_ver');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $model = new PagamentoRecebimento();
        $totais = $model->getTotaisFinanceiros($empresa);

        $this->render('pages/financeiro/pagamentos_recebimentos/pagamentos.twig', [
            'titulo' => 'Pagamentos',
            'page' => 'pagamentos',
            'totais' => $totais
        ]);
    }

    public function criarRecebimento($params)
    {
        $this->setParams($params);
        $this->requirePermission('pagamento_recebimento_criar');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $contaReceberModel = new ContaReceber();
        $faturaModel = new Faturamento();
        $agendamentoModel = new Agendamento();

        $contasReceber = $contaReceberModel->getContasReceber($empresa, []);
        $faturas = $faturaModel->getFaturas($empresa);
        $agendamentos = $agendamentoModel->getAgendamentos($empresa);

        $this->render('pages/financeiro/pagamentos_recebimentos/form_recebimento.twig', [
            'titulo' => 'Novo Recebimento',
            'page' => 'recebimentos',
            'action' => 'criar',
            'contas_receber' => $contasReceber->getResult() ?? [],
            'faturas' => $faturas->getResult() ?? [],
            'agendamentos' => $agendamentos->getResult() ?? []
        ]);
    }

    public function criarPagamento($params)
    {
        $this->setParams($params);
        $this->requirePermission('pagamento_recebimento_criar');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $agendamentoModel = new Agendamento();
        $agendamentos = $agendamentoModel->getAgendamentos($empresa);

        $this->render('pages/financeiro/pagamentos_recebimentos/form_pagamento.twig', [
            'titulo' => 'Novo Pagamento',
            'page' => 'pagamentos',
            'action' => 'criar',
            'agendamentos' => $agendamentos->getResult() ?? []
        ]);
    }

    public function criarRecebimentoSalvar($params)
    {
        $this->setParams($params);
        $this->requirePermission('pagamento_recebimento_criar');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Empresa não encontrada']);
            return;
        }

        $descricao = $_POST['descricao'] ?? '';
        $valor = $this->parseCurrency($_POST['valor'] ?? '0');
        $dataRecebimento = $_POST['data_recebimento'] ?? date('Y-m-d');

        if (empty($descricao) || $valor <= 0 || empty($dataRecebimento)) {
            $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
            return;
        }

        $model = new PagamentoRecebimento();
        
        $data = [
            'empresa' => $empresa,
            'conta_receber_id' => !empty($_POST['conta_receber_id']) ? $_POST['conta_receber_id'] : null,
            'fatura_id' => !empty($_POST['fatura_id']) ? $_POST['fatura_id'] : null,
            'agendamento_id' => !empty($_POST['agendamento_id']) ? $_POST['agendamento_id'] : null,
            'descricao' => $descricao,
            'valor' => $valor,
            'data_recebimento' => $dataRecebimento,
            'data_credito' => $_POST['data_credito'] ?? null,
            'forma_pagamento' => $_POST['forma_pagamento'] ?? 'Transferência',
            'status' => $_POST['status'] ?? 'Confirmado',
            'numero_comprovante' => $_POST['numero_comprovante'] ?? null,
            'observacoes' => $_POST['observacoes'] ?? null
        ];

        $result = $model->criarRecebimento($data);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Recebimento registrado com sucesso']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao registrar recebimento']);
        }
    }

    public function criarPagamentoSalvar($params)
    {
        $this->setParams($params);
        $this->requirePermission('pagamento_recebimento_criar');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Empresa não encontrada']);
            return;
        }

        $descricao = $_POST['descricao'] ?? '';
        $beneficiario = $_POST['beneficiario'] ?? '';
        $valor = $this->parseCurrency($_POST['valor'] ?? '0');
        $dataPagamento = $_POST['data_pagamento'] ?? date('Y-m-d');

        if (empty($descricao) || empty($beneficiario) || $valor <= 0 || empty($dataPagamento)) {
            $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
            return;
        }

        $model = new PagamentoRecebimento();
        
        $data = [
            'empresa' => $empresa,
            'agendamento_id' => !empty($_POST['agendamento_id']) ? $_POST['agendamento_id'] : null,
            'descricao' => $descricao,
            'beneficiario' => $beneficiario,
            'beneficiario_documento' => $_POST['beneficiario_documento'] ?? null,
            'valor' => $valor,
            'data_pagamento' => $dataPagamento,
            'data_vencimento' => $_POST['data_vencimento'] ?? null,
            'forma_pagamento' => $_POST['forma_pagamento'] ?? 'Transferência',
            'status' => $_POST['status'] ?? 'Pendente',
            'tipo' => $_POST['tipo'] ?? 'Assistente',
            'numero_comprovante' => $_POST['numero_comprovante'] ?? null,
            'observacoes' => $_POST['observacoes'] ?? null
        ];

        $result = $model->criarPagamento($data);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Pagamento registrado com sucesso']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao registrar pagamento']);
        }
    }

    public function removerRecebimento($params)
    {
        $this->setParams($params);
        $this->requirePermission('pagamento_recebimento_deletar');
        
        $id = $_POST['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$id || !$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $model = new PagamentoRecebimento();
        $result = $model->removerRecebimento($id, $empresa);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Recebimento removido com sucesso']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao remover recebimento']);
        }
    }

    public function removerPagamento($params)
    {
        $this->setParams($params);
        $this->requirePermission('pagamento_recebimento_deletar');
        
        $id = $_POST['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$id || !$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $model = new PagamentoRecebimento();
        $result = $model->removerPagamento($id, $empresa);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Pagamento removido com sucesso']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao remover pagamento']);
        }
    }

    public function recebimentosDatatable($params)
    {
        $this->setParams($params);
        $this->requirePermission('pagamento_recebimento_ver');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->responseJson(DataTableResponse::format([], 0, 0, (int) ($_GET['draw'] ?? 1)));
            return;
        }

        $paramsDataTable = DataTableResponse::getParams();
        $filtros = [];

        if (!empty($_GET['status'])) {
            $filtros['status'] = $_GET['status'];
        }
        if (!empty($_GET['data_inicio'])) {
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }
        if (!empty($_GET['data_fim'])) {
            $filtros['data_fim'] = $_GET['data_fim'];
        }

        $model = new PagamentoRecebimento();
        $todosRecebimentos = $model->getRecebimentosDataTable($empresa, $filtros);
        
        $total = count($todosRecebimentos);
        $dadosPaginados = array_slice($todosRecebimentos, $paramsDataTable['start'], $paramsDataTable['length']);

        $data = [];
        foreach ($dadosPaginados as $recebimento) {
            $data[] = [
                'id' => $recebimento['id'],
                'descricao' => $recebimento['descricao'],
                'valor' => 'R$ ' . number_format($recebimento['valor'], 2, ',', '.'),
                'data_recebimento' => date('d/m/Y', strtotime($recebimento['data_recebimento'])),
                'forma_pagamento' => $recebimento['forma_pagamento'],
                'status' => $recebimento['status'],
                'conta_descricao' => $recebimento['conta_descricao'] ?? '-',
                'acoes' => ''
            ];
        }

        $this->responseJson(DataTableResponse::format($data, $total, $total, $paramsDataTable['draw']));
    }

    public function pagamentosDatatable($params)
    {
        $this->setParams($params);
        $this->requirePermission('pagamento_recebimento_ver');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->responseJson(DataTableResponse::format([], 0, 0, (int) ($_GET['draw'] ?? 1)));
            return;
        }

        $paramsDataTable = DataTableResponse::getParams();
        $filtros = [];

        if (!empty($_GET['status'])) {
            $filtros['status'] = $_GET['status'];
        }
        if (!empty($_GET['tipo'])) {
            $filtros['tipo'] = $_GET['tipo'];
        }
        if (!empty($_GET['data_inicio'])) {
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }
        if (!empty($_GET['data_fim'])) {
            $filtros['data_fim'] = $_GET['data_fim'];
        }

        $model = new PagamentoRecebimento();
        $todosPagamentos = $model->getPagamentosDataTable($empresa, $filtros);
        
        $total = count($todosPagamentos);
        $dadosPaginados = array_slice($todosPagamentos, $paramsDataTable['start'], $paramsDataTable['length']);

        $data = [];
        foreach ($dadosPaginados as $pagamento) {
            $data[] = [
                'id' => $pagamento['id'],
                'descricao' => $pagamento['descricao'],
                'beneficiario' => $pagamento['beneficiario'],
                'valor' => 'R$ ' . number_format($pagamento['valor'], 2, ',', '.'),
                'data_pagamento' => date('d/m/Y', strtotime($pagamento['data_pagamento'])),
                'tipo' => $pagamento['tipo'],
                'status' => $pagamento['status'],
                'acoes' => ''
            ];
        }

        $this->responseJson(DataTableResponse::format($data, $total, $total, $paramsDataTable['draw']));
    }

    private function parseCurrency($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $value = preg_replace('/[^0-9,.]/', '', $value);
        $value = str_replace(',', '.', str_replace('.', '', $value));
        return (float) $value;
    }
}

