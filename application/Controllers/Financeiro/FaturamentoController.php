<?php

namespace Agencia\Close\Controllers\Financeiro;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Financeiro\Faturamento;
use Agencia\Close\Models\Agendamento\Agendamento;
use Agencia\Close\Helpers\DataTableResponse;

class FaturamentoController extends Controller
{
    public function index($params)
    {
        $this->setParams($params);
        $this->requirePermission('faturamento_ver');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $model = new Faturamento();
        $estatisticas = $model->contarPorStatus($empresa);
        $stats = [];
        foreach ($estatisticas->getResult() as $stat) {
            $stats[$stat['status']] = $stat['total'];
        }

        $this->render('pages/financeiro/faturamento/index.twig', [
            'titulo' => 'Faturamento',
            'page' => 'faturamento',
            'estatisticas' => $stats
        ]);
    }

    public function criar($params)
    {
        $this->setParams($params);
        $this->requirePermission('faturamento_criar');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        $agendamentoId = $_GET['agendamento_id'] ?? null;
        
        if (!$empresa) {
            $this->redirectUrl(DOMAIN . '/login');
            return;
        }

        $agendamentoModel = new Agendamento();
        $agendamentos = $agendamentoModel->getAgendamentos($empresa);
        
        $agendamentoSelecionado = null;
        if ($agendamentoId) {
            $agendamento = $agendamentoModel->getAgendamento($agendamentoId, $empresa);
            $agendamentoSelecionado = $agendamento->getResult()[0] ?? null;
        }

        $this->render('pages/financeiro/faturamento/form.twig', [
            'titulo' => 'Nova Fatura',
            'page' => 'faturamento',
            'action' => 'criar',
            'agendamentos' => $agendamentos->getResult() ?? [],
            'agendamento_selecionado' => $agendamentoSelecionado
        ]);
    }

    public function editar($params)
    {
        $this->setParams($params);
        $this->requirePermission('faturamento_editar');
        
        $id = $params['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$id || !$empresa) {
            $this->redirectUrl(DOMAIN . '/faturamento');
            return;
        }

        $model = new Faturamento();
        $fatura = $model->getFatura($id, $empresa);
        
        if (!$fatura->getResult()) {
            $this->redirectUrl(DOMAIN . '/faturamento');
            return;
        }

        $agendamentoModel = new Agendamento();
        $agendamentos = $agendamentoModel->getAgendamentos($empresa);

        $this->render('pages/financeiro/faturamento/form.twig', [
            'titulo' => 'Editar Fatura',
            'page' => 'faturamento',
            'action' => 'editar',
            'fatura' => $fatura->getResult()[0] ?? null,
            'agendamentos' => $agendamentos->getResult() ?? []
        ]);
    }

    public function criarSalvar($params)
    {
        $this->setParams($params);
        $this->requirePermission('faturamento_criar');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Empresa não encontrada']);
            return;
        }

        $agendamentoId = $_POST['agendamento_id'] ?? null;
        $clienteNome = $_POST['cliente_nome'] ?? '';
        $valorTotal = $this->parseCurrency($_POST['valor_total'] ?? '0');
        $dataEmissao = $_POST['data_emissao'] ?? date('Y-m-d');
        $dataVencimento = $_POST['data_vencimento'] ?? null;

        if (empty($clienteNome) || empty($dataVencimento) || !$agendamentoId) {
            $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
            return;
        }

        $model = new Faturamento();
        
        $data = [
            'empresa' => $empresa,
            'agendamento_id' => $agendamentoId,
            'numero_fatura' => $model->gerarProximoNumeroFatura($empresa),
            'cliente_nome' => $clienteNome,
            'cliente_documento' => $_POST['cliente_documento'] ?? null,
            'cliente_endereco' => $_POST['cliente_endereco'] ?? null,
            'valor_total' => $valorTotal,
            'valor_desconto' => $this->parseCurrency($_POST['valor_desconto'] ?? '0'),
            'valor_acrescimo' => $this->parseCurrency($_POST['valor_acrescimo'] ?? '0'),
            'data_emissao' => $dataEmissao,
            'data_vencimento' => $dataVencimento,
            'status' => $_POST['status'] ?? 'Rascunho',
            'tipo_fatura' => $_POST['tipo_fatura'] ?? 'Nota Fiscal',
            'observacoes' => $_POST['observacoes'] ?? null
        ];

        $result = $model->criar($data);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Fatura criada com sucesso']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao criar fatura']);
        }
    }

    public function editarSalvar($params)
    {
        $this->setParams($params);
        $this->requirePermission('faturamento_editar');
        
        $id = $_POST['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$id || !$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $data = [
            'agendamento_id' => $_POST['agendamento_id'] ?? null,
            'cliente_nome' => $_POST['cliente_nome'] ?? null,
            'cliente_documento' => $_POST['cliente_documento'] ?? null,
            'cliente_endereco' => $_POST['cliente_endereco'] ?? null,
            'valor_total' => $this->parseCurrency($_POST['valor_total'] ?? '0'),
            'valor_desconto' => $this->parseCurrency($_POST['valor_desconto'] ?? '0'),
            'valor_acrescimo' => $this->parseCurrency($_POST['valor_acrescimo'] ?? '0'),
            'data_emissao' => $_POST['data_emissao'] ?? null,
            'data_vencimento' => $_POST['data_vencimento'] ?? null,
            'status' => $_POST['status'] ?? null,
            'tipo_fatura' => $_POST['tipo_fatura'] ?? null,
            'observacoes' => $_POST['observacoes'] ?? null
        ];

        $model = new Faturamento();
        $result = $model->atualizar($id, $empresa, $data);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Fatura atualizada com sucesso']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar fatura']);
        }
    }

    public function remover($params)
    {
        $this->setParams($params);
        $this->requirePermission('faturamento_deletar');
        
        $id = $_POST['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$id || !$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $model = new Faturamento();
        $result = $model->remover($id, $empresa);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Fatura removida com sucesso']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao remover fatura']);
        }
    }

    public function datatable($params)
    {
        $this->setParams($params);
        $this->requirePermission('faturamento_ver');
        
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
        if (!empty($paramsDataTable['search'])) {
            $filtros['numero_fatura'] = $paramsDataTable['search'];
        }

        $model = new Faturamento();
        $todasFaturas = $model->getFaturasDataTable($empresa, $filtros);
        
        $total = count($todasFaturas);
        $dadosPaginados = array_slice($todasFaturas, $paramsDataTable['start'], $paramsDataTable['length']);

        $data = [];
        foreach ($dadosPaginados as $fatura) {
            $data[] = [
                'id' => $fatura['id'],
                'numero_fatura' => $fatura['numero_fatura'],
                'cliente_nome' => $fatura['cliente_nome'],
                'valor_liquido' => 'R$ ' . number_format($fatura['valor_liquido'], 2, ',', '.'),
                'data_emissao' => date('d/m/Y', strtotime($fatura['data_emissao'])),
                'data_vencimento' => date('d/m/Y', strtotime($fatura['data_vencimento'])),
                'status' => $fatura['status'],
                'tipo_pericia' => $fatura['tipo_pericia'] ?? '-',
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

