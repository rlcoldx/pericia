<?php
// MIGRATIONS
$router->namespace("Agencia\Close\Controllers\Migration");
$router->get("/migrations", "MigrationController:index", 'migrations');
$router->post("/migrations/run", "MigrationController:run", 'migrations.run');
$router->post("/migrations/run-single", "MigrationController:runSingle", 'migrations.runSingle');
$router->post("/migrations/rollback", "MigrationController:rollback", 'migrations.rollback');
$router->get("/migrations/status", "MigrationController:status", 'migrations.status');

