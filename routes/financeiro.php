<?php
// PAGE FINANCEIRO - CONTAS A RECEBER
$router->namespace("Agencia\Close\Controllers\Financeiro");
$router->get("/contas-receber", "ContaReceberController:index");
$router->get("/contas-receber/estatisticas", "ContaReceberController:estatisticas");
$router->get("/contas-receber/add", "ContaReceberController:criar");
$router->get("/contas-receber/edit/{id}", "ContaReceberController:editar");
$router->get("/contas-receber/view/{id}", "ContaReceberController:visualizar");
$router->post("/contas-receber/add/save", "ContaReceberController:criarSalvar");
$router->post("/contas-receber/edit/save", "ContaReceberController:editarSalvar");
$router->post("/contas-receber/remover", "ContaReceberController:remover");
$router->get("/contas-receber/datatable", "ContaReceberController:datatable");

// PAGE FINANCEIRO - FATURAMENTO
$router->namespace("Agencia\Close\Controllers\Financeiro");
$router->get("/faturamento", "FaturamentoController:index");
$router->get("/faturamento/add", "FaturamentoController:criar");
$router->get("/faturamento/edit/{id}", "FaturamentoController:editar");
$router->post("/faturamento/add/save", "FaturamentoController:criarSalvar");
$router->post("/faturamento/edit/save", "FaturamentoController:editarSalvar");
$router->post("/faturamento/remover", "FaturamentoController:remover");
$router->get("/faturamento/datatable", "FaturamentoController:datatable");

// PAGE FINANCEIRO - RELATÓRIOS FINANCEIROS
$router->namespace("Agencia\Close\Controllers\Financeiro");
$router->get("/relatorio-financeiro", "RelatorioFinanceiroController:index");
$router->get("/relatorio-financeiro/exportar", "RelatorioFinanceiroController:exportar");

// PAGE FINANCEIRO - PAGAMENTOS E RECEBIMENTOS
$router->namespace("Agencia\Close\Controllers\Financeiro");
$router->get("/recebimentos", "PagamentoRecebimentoController:recebimentos");
$router->get("/recebimentos/add", "PagamentoRecebimentoController:criarRecebimento");
$router->post("/recebimentos/add/save", "PagamentoRecebimentoController:criarRecebimentoSalvar");
$router->post("/recebimentos/remover", "PagamentoRecebimentoController:removerRecebimento");
$router->get("/recebimentos/datatable", "PagamentoRecebimentoController:recebimentosDatatable");

$router->get("/pagamentos", "PagamentoRecebimentoController:pagamentos");
$router->get("/pagamentos/add", "PagamentoRecebimentoController:criarPagamento");
$router->post("/pagamentos/add/save", "PagamentoRecebimentoController:criarPagamentoSalvar");
$router->post("/pagamentos/remover", "PagamentoRecebimentoController:removerPagamento");
$router->get("/pagamentos/datatable", "PagamentoRecebimentoController:pagamentosDatatable");

