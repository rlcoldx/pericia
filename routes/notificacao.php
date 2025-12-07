<?php
// PAINEL NOTIFICACAO
$router->namespace("Agencia\Close\Controllers\Notificacao");
$router->get("/notificacao/criar", "NotificacaoController:index");
$router->post("/notificacao/enviar", "NotificacaoController:enviarNotificacao");
$router->get("/notificacao/reserva/alerta", "NotificacaoController:reservaAlerta");
$router->get("/notificacoes", "NotificacaoController:listarUsuario");
$router->post("/notificacoes/marcar-lida/{id}", "NotificacaoController:marcarComoLida");
$router->get("/notificacoes/contar-nao-lidas", "NotificacaoController:contarNaoLidas");
