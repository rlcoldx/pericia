<?php
// PAGE ASSISTENTES
$router->namespace("Agencia\Close\Controllers\Assistente");
$router->get("/assistentes", "AssistenteController:index");
$router->get("/assistentes/add", "AssistenteController:criar");
$router->post("/assistentes/add/save", "AssistenteController:salvarCriar");
$router->get("/assistentes/editar/{id}", "AssistenteController:editar");
$router->post("/assistentes/editar/save", "AssistenteController:salvarEditar");
$router->post("/assistentes/remover", "AssistenteController:remover");
$router->get("/assistentes/datatable", "AssistenteController:datatable");
$router->get("/assistentes/importar", "AssistenteController:importar");
$router->post("/assistentes/importar/processar", "AssistenteController:processarImportacao");
