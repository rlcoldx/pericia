<?php
// PAGE QUESITOS
$router->namespace("Agencia\Close\Controllers\Quesito");
$router->get("/quesitos", "QuesitoController:index");
$router->get("/quesitos/add", "QuesitoController:criar");
$router->post("/quesitos/add/save", "QuesitoController:salvarCriar");
$router->get("/quesitos/editar/{id}", "QuesitoController:editar");
$router->post("/quesitos/editar/save", "QuesitoController:salvarEditar");
$router->get("/quesitos/datatable", "QuesitoController:datatable");

