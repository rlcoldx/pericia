<?php
// PAGE HOME
$router->namespace("Agencia\Close\Controllers\Home");
$router->get("/", "HomeController:index");
$router->get("/home/estatisticas", "HomeController:getEstatisticasAjax");
$router->get("/home/tarefas/datatable", "HomeController:tarefasDatatable");
