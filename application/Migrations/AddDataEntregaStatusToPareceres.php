<?php

namespace Agencia\Close\Migrations;

class AddDataEntregaStatusToPareceres extends Migration
{
    public function up(): void
    {
        // Adiciona data_entrega_parecer se ainda não existir
        if (!$this->columnExists('pareceres', 'data_entrega_parecer')) {
            $this->executeQuery("ALTER TABLE `pareceres`
                                  ADD COLUMN `data_entrega_parecer` date DEFAULT NULL COMMENT 'Data de entrega do parecer' AFTER `data_fatal`");
        }

        // Adiciona status_parecer se ainda não existir
        if (!$this->columnExists('pareceres', 'status_parecer')) {
            $this->executeQuery("ALTER TABLE `pareceres`
                                  ADD COLUMN `status_parecer` enum('OK','FAVORAVEL','DESFAVORAVEL','PARCIAL FAVORAVEL','NR (NÃO REALIZADO)') DEFAULT NULL COMMENT 'Status do parecer' AFTER `data_entrega_parecer`");
        }
    }

    public function down(): void
    {
        if ($this->columnExists('pareceres', 'status_parecer')) {
            $this->executeQuery("ALTER TABLE `pareceres` DROP COLUMN `status_parecer`");
        }

        if ($this->columnExists('pareceres', 'data_entrega_parecer')) {
            $this->executeQuery("ALTER TABLE `pareceres` DROP COLUMN `data_entrega_parecer`");
        }
    }
}
