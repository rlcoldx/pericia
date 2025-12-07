<?php
// PAGE AGENDAMENTO
$router->namespace("Agencia\Close\Controllers\Agendamento");
$router->get("/agendamento", "AgendamentoController:index");
$router->get("/agendamento/add", "AgendamentoController:criar");
$router->get("/agendamento/edit/{id}", "AgendamentoController:editar");
$router->get("/agendamento/view/{id}", "AgendamentoController:visualizar");
$router->post("/agendamento/add/save", "AgendamentoController:criarSalvar");
$router->post("/agendamento/edit/save", "AgendamentoController:editarSalvar");
$router->post("/agendamento/remover", "AgendamentoController:remover");
$router->post("/agendamento/alterar-status", "AgendamentoController:alterarStatus");
$router->get("/agendamento/datatable", "AgendamentoController:datatable");
$router->get("/agendamento/calendario", "AgendamentoController:calendario");
$router->get("/agendamento/calendario/eventos", "AgendamentoController:calendarioEventos");

