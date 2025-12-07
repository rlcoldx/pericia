<?php

$router->namespace("Agencia\Close\Controllers\Parecer");
$router->get("/pareceres", "ParecerController:index");
$router->get("/pareceres/add", "ParecerController:criar");
$router->post("/pareceres/add/save", "ParecerController:salvarCriar");
$router->get("/pareceres/editar/{id}", "ParecerController:editar");
$router->post("/pareceres/editar/save", "ParecerController:salvarEditar");
$router->get("/pareceres/datatable", "ParecerController:datatable");
