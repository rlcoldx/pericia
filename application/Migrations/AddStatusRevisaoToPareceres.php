<?php

namespace Agencia\Close\Migrations;

class AddStatusRevisaoToPareceres extends Migration
{
    public function up(): void
    {
        // Adiciona status_revisao se ainda não existir
        if (!$this->columnExists('pareceres', 'status_revisao')) {
            $this->executeQuery("ALTER TABLE `pareceres`
                                  ADD COLUMN `status_revisao` enum('Revisão de Parecer','Enviado') DEFAULT NULL COMMENT 'Status de revisão do parecer' AFTER `status_parecer`");
        }
    }

    public function down(): void
    {
        if ($this->columnExists('pareceres', 'status_revisao')) {
            $this->executeQuery("ALTER TABLE `pareceres` DROP COLUMN `status_revisao`");
        }
    }
}
