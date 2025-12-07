<?php
// EMAIL TEMPLATES
$router->namespace("Agencia\Close\Controllers\EmailTemplate");
$router->get("/email-templates", "EmailTemplateController:index");
$router->get("/email-templates/add", "EmailTemplateController:criar");
$router->post("/email-templates/add/save", "EmailTemplateController:salvarCriar");
$router->get("/email-templates/editar/{id}", "EmailTemplateController:editar");
$router->post("/email-templates/editar/save", "EmailTemplateController:salvarEditar");
$router->delete("/email-templates/remover/{id}", "EmailTemplateController:remover");
