<?php
// PAGE RECLAMADAS
$router->namespace("Agencia\Close\Controllers\Reclamada");
$router->get("/reclamadas", "ReclamadaController:index");
$router->get("/reclamadas/add", "ReclamadaController:criar");
$router->post("/reclamadas/add/save", "ReclamadaController:salvarCriar");
$router->get("/reclamadas/editar/{id}", "ReclamadaController:editar");
$router->post("/reclamadas/editar/save", "ReclamadaController:salvarEditar");
$router->post("/reclamadas/remover", "ReclamadaController:remover");
