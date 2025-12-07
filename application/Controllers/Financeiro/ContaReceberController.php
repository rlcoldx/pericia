<?php

namespace Agencia\Close\Controllers\Financeiro;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Models\Financeiro\ContaReceber;
use Agencia\Close\Models\Agendamento\Agendamento;
use Agencia\Close\Helpers\DataTableResponse;

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

        $this->render('pages/financeiro/contas_receber/form.twig', [
            'titulo' => 'Nova Conta a Receber',
            'page' => 'contas_receber',
            'action' => 'criar',
            'agendamentos' => $agendamentos->getResult() ?? []
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

        $this->render('pages/financeiro/contas_receber/form.twig', [
            'titulo' => 'Editar Conta a Receber',
            'page' => 'contas_receber',
            'action' => 'editar',
            'conta' => $conta->getResult()[0] ?? null,
            'agendamentos' => $agendamentos->getResult() ?? []
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
        $this->setParams($params);
        $this->requirePermission('contas_receber_criar');
        
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Empresa não encontrada']);
            return;
        }

        $descricao = $_POST['descricao'] ?? '';
        $clienteNome = $_POST['cliente_nome'] ?? '';
        $valorTotal = $_POST['valor_total'] ?? '0';
        $dataVencimento = $_POST['data_vencimento'] ?? null;

        if (empty($descricao) || empty($clienteNome) || empty($dataVencimento)) {
            $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
            return;
        }

        $valorTotal = $this->parseCurrency($valorTotal);
        $valorRecebido = $this->parseCurrency($_POST['valor_recebido'] ?? '0');

        $data = [
            'empresa' => $empresa,
            'agendamento_id' => !empty($_POST['agendamento_id']) ? $_POST['agendamento_id'] : null,
            'descricao' => $descricao,
            'cliente_nome' => $clienteNome,
            'cliente_documento' => $_POST['cliente_documento'] ?? null,
            'local_pericia' => $_POST['local_pericia'] ?? null,
            'reclamante_nome' => $_POST['reclamante_nome'] ?? null,
            'numero_processo' => $_POST['numero_processo'] ?? null,
            'valor_total' => $valorTotal,
            'valor_recebido' => $valorRecebido,
            'data_vencimento' => $dataVencimento,
            'data_emissao' => $_POST['data_emissao'] ?? date('Y-m-d'),
            'data_pericia' => $_POST['data_pericia'] ?? null,
            'data_envio' => $_POST['data_envio'] ?? null,
            'status' => $_POST['status'] ?? null,
            'tipo' => $_POST['tipo'] ?? 'Perícia',
            'etapa' => $_POST['etapa'] ?? 'PERICIA',
            'situacao' => $_POST['situacao'] ?? null,
            'data_situacao' => $_POST['data_situacao'] ?? null,
            'numero_nota_fiscal' => $_POST['numero_nota_fiscal'] ?? null,
            'numero_boleto' => $_POST['numero_boleto'] ?? null,
            'assistente_nome' => $_POST['assistente_nome'] ?? null,
            'valor_assistente' => !empty($_POST['valor_assistente']) ? $this->parseCurrency($_POST['valor_assistente']) : null,
            'observacoes' => $_POST['observacoes'] ?? null
        ];

        $model = new ContaReceber();
        $result = $model->criar($data);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Conta a receber criada com sucesso']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao criar conta a receber']);
        }
    }

    public function editarSalvar($params)
    {
        $this->setParams($params);
        $this->requirePermission('contas_receber_editar');
        
        $id = $_POST['id'] ?? null;
        $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
        
        if (!$id || !$empresa) {
            $this->responseJson(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $descricao = $_POST['descricao'] ?? '';
        $clienteNome = $_POST['cliente_nome'] ?? '';
        $valorTotal = $_POST['valor_total'] ?? '0';
        $dataVencimento = $_POST['data_vencimento'] ?? null;

        if (empty($descricao) || empty($clienteNome) || empty($dataVencimento)) {
            $this->responseJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
            return;
        }

        $valorTotal = $this->parseCurrency($valorTotal);
        $valorRecebido = $this->parseCurrency($_POST['valor_recebido'] ?? '0');

        $data = [
            'agendamento_id' => !empty($_POST['agendamento_id']) ? $_POST['agendamento_id'] : null,
            'descricao' => $descricao,
            'cliente_nome' => $clienteNome,
            'cliente_documento' => $_POST['cliente_documento'] ?? null,
            'local_pericia' => $_POST['local_pericia'] ?? null,
            'reclamante_nome' => $_POST['reclamante_nome'] ?? null,
            'numero_processo' => $_POST['numero_processo'] ?? null,
            'valor_total' => $valorTotal,
            'valor_recebido' => $valorRecebido,
            'data_vencimento' => $dataVencimento,
            'data_emissao' => $_POST['data_emissao'] ?? null,
            'data_pericia' => $_POST['data_pericia'] ?? null,
            'data_envio' => $_POST['data_envio'] ?? null,
            'tipo' => $_POST['tipo'] ?? 'Perícia',
            'etapa' => $_POST['etapa'] ?? 'PERICIA',
            'situacao' => $_POST['situacao'] ?? null,
            'data_situacao' => $_POST['data_situacao'] ?? null,
            'numero_nota_fiscal' => $_POST['numero_nota_fiscal'] ?? null,
            'numero_boleto' => $_POST['numero_boleto'] ?? null,
            'assistente_nome' => $_POST['assistente_nome'] ?? null,
            'valor_assistente' => !empty($_POST['valor_assistente']) ? $this->parseCurrency($_POST['valor_assistente']) : null,
            'observacoes' => $_POST['observacoes'] ?? null
        ];

        $model = new ContaReceber();
        $result = $model->atualizar($id, $empresa, $data);
        
        if ($result) {
            $this->responseJson(['success' => true, 'message' => 'Conta a receber atualizada com sucesso']);
        } else {
            $this->responseJson(['success' => false, 'message' => 'Erro ao atualizar conta a receber']);
        }
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
            $data[] = [
                'id' => $conta['id'],
                'local' => $conta['local_pericia_completo'] ?? '-',
                'reclamante' => $conta['reclamante_nome_completo'] ?? '-',
                'tipo' => $conta['tipo'] ?? '-',
                'etapa' => $conta['etapa'] ?? 'PERICIA',
                'valor' => 'R$ ' . number_format($conta['valor_total'], 2, ',', '.'),
                'processo' => $conta['numero_processo_completo'] ?? '-',
                'data_pericia' => $conta['data_pericia_completo'] ? date('d/m/Y', strtotime($conta['data_pericia_completo'])) : '-',
                'situacao' => $conta['situacao'] ?? '-',
                'numero_pedido' => $conta['numero_pedido_cliente'] ?? '-',
                'numero_nota_fiscal' => $conta['numero_nota_fiscal'] ?? '-',
                'numero_boleto' => $conta['numero_boleto'] ?? '-',
                'data_envio' => $conta['data_envio'] ? date('d/m/Y', strtotime($conta['data_envio'])) : ($conta['data_envio_financeiro'] ? date('d/m/Y', strtotime($conta['data_envio_financeiro'])) : '-'),
                'prazo' => date('d/m/Y', strtotime($conta['data_vencimento'])),
                'status' => $conta['status_pagamento_agendamento'] ?? $conta['status'],
                'assistente' => $conta['assistente_nome_completo'] ?? '-',
                'valor_assistente' => $conta['valor_assistente_completo'] ? 'R$ ' . number_format($conta['valor_assistente_completo'], 2, ',', '.') : '-',
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
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Remove tudo exceto números, vírgula e ponto
        $value = preg_replace('/[^0-9,.]/', '', $value);
        
        // Substitui vírgula por ponto
        $value = str_replace(',', '.', str_replace('.', '', $value));
        
        return (float) $value;
    }
}

