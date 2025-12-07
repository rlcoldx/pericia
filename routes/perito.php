<?php
// PAGE PERITO
$router->namespace("Agencia\Close\Controllers\Perito");
$router->get("/perito", "PeritoController:index");
$router->get("/perito/add", "PeritoController:criar");
$router->get("/perito/edit/{id}", "PeritoController:editar");
$router->get("/perito/view/{id}", "PeritoController:visualizar");
$router->post("/perito/add/save", "PeritoController:criarSalvar");
$router->post("/perito/edit/save", "PeritoController:editarSalvar");
$router->post("/perito/remover", "PeritoController:remover");
$router->get("/perito/datatable", "PeritoController:datatable");

