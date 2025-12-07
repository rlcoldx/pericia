<?php

use CoffeeCode\Router\Router;

$router = new Router(DOMAIN);

// SITE
//require  __DIR__ . '/app/login.php';

// SITE
require  __DIR__ . '/login.php';
require  __DIR__ . '/home.php';
require  __DIR__ . '/equipes.php';
require  __DIR__ . '/perito.php';
require  __DIR__ . '/reclamadas.php';
require  __DIR__ . '/reclamantes.php';
require  __DIR__ . '/quesito.php';
require  __DIR__ . '/manifestacao_impugnacao.php';
require  __DIR__ . '/parecer.php';
require  __DIR__ . '/agendamento.php';
require  __DIR__ . '/financeiro.php';
require  __DIR__ . '/notificacao.php';
require  __DIR__ . '/email_template.php';
require  __DIR__ . '/migration.php';

// ERROR
$router->group("error")->namespace("Agencia\Close\Controllers\Error");
$router->get("/{errorCode}", "ErrorController:show", 'error');

$router->dispatch();
if ($router->error()) {
    echo "Página não encontrada.";
}