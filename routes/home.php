<?php
// PAGE HOME
$router->namespace("Agencia\Close\Controllers\Home");
$router->get("/", "HomeController:index");
$router->get("/home/estatisticas", "HomeController:getEstatisticasAjax");
