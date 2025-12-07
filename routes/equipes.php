<?php
// PAGE EQUIPE
$router->namespace("Agencia\Close\Controllers\Equipe");
$router->get("/equipe", "EquipeController:index");
$router->get("/equipe/add", "EquipeController:criar");
$router->get("/equipe/edit/{id}", "EquipeController:editar");
$router->post("/equipe/add/save", "EquipeController:criarSalvar");
$router->post("/equipe/edit/save", "EquipeController:editarSalvar");
$router->post("/equipe/remover", "EquipeController:remover");

// PAGE EQUIPE CARGOS
$router->namespace("Agencia\Close\Controllers\Cargos");
$router->get("/cargos", "CargosController:index");
$router->get("/cargos/add", "CargosController:criar");
$router->get("/cargos/edit/{id}", "CargosController:editar");
$router->post("/cargos/add/save", "CargosController:criarSalvar");
$router->post("/cargos/edit/save", "CargosController:editarSalvar");
$router->post("/cargos/remover", "CargosController:remover");
$router->get("/cargos/permissoes/{id}", "CargosController:verPermissoes");