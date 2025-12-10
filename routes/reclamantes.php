<?php
// PAGE RECLAMANTES
$router->namespace("Agencia\Close\Controllers\Reclamante");
$router->get("/reclamantes", "ReclamanteController:index");
$router->get("/reclamantes/add", "ReclamanteController:criar");
$router->post("/reclamantes/add/save", "ReclamanteController:salvarCriar");
$router->get("/reclamantes/editar/{id}", "ReclamanteController:editar");
$router->post("/reclamantes/editar/save", "ReclamanteController:salvarEditar");
$router->post("/reclamantes/remover", "ReclamanteController:remover");
$router->get("/reclamantes/datatable", "ReclamanteController:datatable");
