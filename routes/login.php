<?php
// LOGIN
$router->namespace("Agencia\Close\Controllers\Login");
$router->get("/login", "LoginController:index");
$router->get("/login/recover", "LoginController:recover");
$router->post("/login/sign", "LoginController:sign");
$router->get("/login/logout", "LoginController:logout");