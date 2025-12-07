<?php
// PAGE MANIFESTACOES E IMPUGNACOES
$router->namespace("Agencia\Close\Controllers\ManifestacaoImpugnacao");
$router->get("/manifestacoes-impugnacoes", "ManifestacaoImpugnacaoController:index");
$router->get("/manifestacoes-impugnacoes/add", "ManifestacaoImpugnacaoController:criar");
$router->post("/manifestacoes-impugnacoes/add/save", "ManifestacaoImpugnacaoController:salvarCriar");
$router->get("/manifestacoes-impugnacoes/editar/{id}", "ManifestacaoImpugnacaoController:editar");
$router->post("/manifestacoes-impugnacoes/editar/save", "ManifestacaoImpugnacaoController:salvarEditar");
$router->get("/manifestacoes-impugnacoes/datatable", "ManifestacaoImpugnacaoController:datatable");
