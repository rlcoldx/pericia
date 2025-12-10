<?php
// PAGE CONFIGURAÇÕES
$router->namespace("Agencia\Close\Controllers\Configuracao");
$router->get("/configuracao", "ConfiguracaoController:index");
$router->post("/configuracao/salvar", "ConfiguracaoController:salvar");
