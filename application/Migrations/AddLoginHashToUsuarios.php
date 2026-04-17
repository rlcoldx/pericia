<?php

namespace Agencia\Close\Migrations;

/**
 * Lista de hashes (SHA-256 hex) de tokens de login persistente, separados por vírgula.
 * Cada dispositivo recebe um token aleatório no cookie; no banco guarda-se apenas o hash.
 */
class AddLoginHashToUsuarios extends Migration
{
    public function up(): void
    {
        if (!$this->tableExists('usuarios')) {
            return;
        }
        if ($this->columnExists('usuarios', 'loginHash')) {
            return;
        }

        $this->executeQuery(
            "ALTER TABLE `usuarios`
             ADD COLUMN `loginHash` TEXT NULL DEFAULT NULL COMMENT 'Hashes SHA-256 de tokens persistentes (vírgula)'"
        );
    }

    public function down(): void
    {
        if (!$this->tableExists('usuarios') || !$this->columnExists('usuarios', 'loginHash')) {
            return;
        }
        $this->executeQuery('ALTER TABLE `usuarios` DROP COLUMN `loginHash`');
    }
}
