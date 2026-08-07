<?php

namespace Agencia\Close\Migrations;

/**
 * Tokens de login permanente por dispositivo (substitui depender só da sessão PHP / loginHash TEXT).
 */
class CreateUserPersistentTokens extends Migration
{
    public function up(): void
    {
        if ($this->tableExists('user_persistent_tokens')) {
            return;
        }

        $this->executeQuery(
            "CREATE TABLE `user_persistent_tokens` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `last_used_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_token_hash` (`token_hash`),
                KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(): void
    {
        if ($this->tableExists('user_persistent_tokens')) {
            $this->executeQuery('DROP TABLE `user_persistent_tokens`');
        }
    }
}
