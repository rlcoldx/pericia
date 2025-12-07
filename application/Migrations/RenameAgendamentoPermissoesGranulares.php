<?php

namespace Agencia\Close\Migrations;

class RenameAgendamentoPermissoesGranulares extends Migration
{
    public function up(): void
    {
        // Mapeamento de permissões antigas para novas
        $renames = [
            // PRIMEIRO PASSO (antigo CLOVIS)
            'agendamento_clovis_data_entrada' => 'agendamento_primeiro_passo_data_entrada',
            'agendamento_clovis_data_pericia' => 'agendamento_primeiro_passo_data_pericia',
            'agendamento_clovis_hora' => 'agendamento_primeiro_passo_hora',
            'agendamento_clovis_numero_processo' => 'agendamento_primeiro_passo_numero_processo',
            'agendamento_clovis_vara' => 'agendamento_primeiro_passo_vara',
            'agendamento_clovis_local' => 'agendamento_primeiro_passo_local',
            'agendamento_clovis_reclamante' => 'agendamento_primeiro_passo_reclamante',
            'agendamento_clovis_perito' => 'agendamento_primeiro_passo_perito',
            'agendamento_clovis_cliente' => 'agendamento_primeiro_passo_cliente',
            'agendamento_clovis_valor_cobrado' => 'agendamento_primeiro_passo_valor_cobrado',
            'agendamento_clovis_tipo' => 'agendamento_primeiro_passo_tipo',
            'agendamento_clovis_numero_tipo' => 'agendamento_primeiro_passo_numero_tipo',
            'agendamento_clovis_assistente' => 'agendamento_primeiro_passo_assistente',
            'agendamento_clovis_valor_pago_assistente' => 'agendamento_primeiro_passo_valor_pago_assistente',
            
            // SEGUNDO PASSO (antigo MARCELO)
            'agendamento_marcelo_data_realizada' => 'agendamento_segundo_passo_data_realizada',
            'agendamento_marcelo_data_fatal' => 'agendamento_segundo_passo_data_fatal',
            'agendamento_marcelo_data_entrega_parecer' => 'agendamento_segundo_passo_data_entrega_parecer',
            'agendamento_marcelo_status_parecer' => 'agendamento_segundo_passo_status_parecer',
            'agendamento_marcelo_obs_parecer' => 'agendamento_segundo_passo_obs_parecer',
            
            // TERCEIRO PASSO (antigo MAURO)
            'agendamento_mauro_data_pagamento_assistente' => 'agendamento_terceiro_passo_data_pagamento_assistente',
            'agendamento_mauro_numero_pedido' => 'agendamento_terceiro_passo_numero_pedido',
            'agendamento_mauro_numero_nota_fiscal' => 'agendamento_terceiro_passo_numero_nota_fiscal',
            'agendamento_mauro_numero_boleto' => 'agendamento_terceiro_passo_numero_boleto',
            'agendamento_mauro_data_envio' => 'agendamento_terceiro_passo_data_envio',
            'agendamento_mauro_data_vencimento' => 'agendamento_terceiro_passo_data_vencimento',
            'agendamento_mauro_status_pagamento' => 'agendamento_terceiro_passo_status_pagamento'
        ];
        
        foreach ($renames as $oldName => $newName) {
            // Verifica se a permissão antiga existe
            $checkSql = "SELECT id FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute([':nome' => $oldName]);
            
            if ($stmt->rowCount() > 0) {
                // Verifica se a nova permissão já existe
                $checkNewSql = "SELECT id FROM permissoes WHERE nome = :nome";
                $stmtNew = $this->conn->prepare($checkNewSql);
                $stmtNew->execute([':nome' => $newName]);
                
                if ($stmtNew->rowCount() == 0) {
                    // Renomeia a permissão
                    $updateSql = "UPDATE permissoes SET nome = :new_name WHERE nome = :old_name";
                    $updateStmt = $this->conn->prepare($updateSql);
                    $updateStmt->execute([
                        ':new_name' => $newName,
                        ':old_name' => $oldName
                    ]);
                    
                    // O relacionamento será mantido automaticamente pois apenas renomeamos a permissão
                    // O ID da permissão permanece o mesmo, então não precisa atualizar cargo_permissoes e usuario_permissoes
                } else {
                    // Se a nova já existe, atualiza os relacionamentos para apontar para a nova permissão
                    // Busca o ID da nova permissão
                    $getNewIdSql = "SELECT id FROM permissoes WHERE nome = :new_name LIMIT 1";
                    $getNewIdStmt = $this->conn->prepare($getNewIdSql);
                    $getNewIdStmt->execute([':new_name' => $newName]);
                    $newPermissaoId = $getNewIdStmt->fetch(\PDO::FETCH_ASSOC)['id'] ?? null;
                    
                    if ($newPermissaoId) {
                        // Busca o ID da permissão antiga
                        $getOldIdSql = "SELECT id FROM permissoes WHERE nome = :old_name LIMIT 1";
                        $getOldIdStmt = $this->conn->prepare($getOldIdSql);
                        $getOldIdStmt->execute([':old_name' => $oldName]);
                        $oldPermissaoId = $getOldIdStmt->fetch(\PDO::FETCH_ASSOC)['id'] ?? null;
                        
                        if ($oldPermissaoId && $oldPermissaoId != $newPermissaoId) {
                            // Atualiza cargo_permissoes
                            $updateCargoSql = "UPDATE cargo_permissoes SET permissao_id = :new_id WHERE permissao_id = :old_id";
                            $updateCargoStmt = $this->conn->prepare($updateCargoSql);
                            $updateCargoStmt->execute([
                                ':new_id' => $newPermissaoId,
                                ':old_id' => $oldPermissaoId
                            ]);
                            
                            // Atualiza usuario_permissoes
                            $updateUsuarioSql = "UPDATE usuario_permissoes SET permissao_id = :new_id WHERE permissao_id = :old_id";
                            $updateUsuarioStmt = $this->conn->prepare($updateUsuarioSql);
                            $updateUsuarioStmt->execute([
                                ':new_id' => $newPermissaoId,
                                ':old_id' => $oldPermissaoId
                            ]);
                            
                            // Remove a permissão antiga
                            $deleteSql = "DELETE FROM permissoes WHERE id = :old_id";
                            $deleteStmt = $this->conn->prepare($deleteSql);
                            $deleteStmt->execute([':old_id' => $oldPermissaoId]);
                        }
                    }
                }
            }
        }
        
        // Atualiza os grupos das permissões
        $grupos = [
            'agendamento_primeiro_passo_' => 'Agendamentos - Primeiro Passo',
            'agendamento_segundo_passo_' => 'Agendamentos - Segundo Passo',
            'agendamento_terceiro_passo_' => 'Agendamentos - Terceiro Passo'
        ];
        
        foreach ($grupos as $prefix => $grupo) {
            $updateGrupoSql = "UPDATE permissoes SET grupo = :grupo WHERE nome LIKE :prefix";
            $updateGrupoStmt = $this->conn->prepare($updateGrupoSql);
            $updateGrupoStmt->execute([
                ':grupo' => $grupo,
                ':prefix' => $prefix . '%'
            ]);
        }
    }

    public function down(): void
    {
        // Mapeamento reverso (novas para antigas)
        $renames = [
            // PRIMEIRO PASSO volta para CLOVIS
            'agendamento_primeiro_passo_data_entrada' => 'agendamento_clovis_data_entrada',
            'agendamento_primeiro_passo_data_pericia' => 'agendamento_clovis_data_pericia',
            'agendamento_primeiro_passo_hora' => 'agendamento_clovis_hora',
            'agendamento_primeiro_passo_numero_processo' => 'agendamento_clovis_numero_processo',
            'agendamento_primeiro_passo_vara' => 'agendamento_clovis_vara',
            'agendamento_primeiro_passo_local' => 'agendamento_clovis_local',
            'agendamento_primeiro_passo_reclamante' => 'agendamento_clovis_reclamante',
            'agendamento_primeiro_passo_perito' => 'agendamento_clovis_perito',
            'agendamento_primeiro_passo_cliente' => 'agendamento_clovis_cliente',
            'agendamento_primeiro_passo_valor_cobrado' => 'agendamento_clovis_valor_cobrado',
            'agendamento_primeiro_passo_tipo' => 'agendamento_clovis_tipo',
            'agendamento_primeiro_passo_numero_tipo' => 'agendamento_clovis_numero_tipo',
            'agendamento_primeiro_passo_assistente' => 'agendamento_clovis_assistente',
            'agendamento_primeiro_passo_valor_pago_assistente' => 'agendamento_clovis_valor_pago_assistente',
            
            // SEGUNDO PASSO volta para MARCELO
            'agendamento_segundo_passo_data_realizada' => 'agendamento_marcelo_data_realizada',
            'agendamento_segundo_passo_data_fatal' => 'agendamento_marcelo_data_fatal',
            'agendamento_segundo_passo_data_entrega_parecer' => 'agendamento_marcelo_data_entrega_parecer',
            'agendamento_segundo_passo_status_parecer' => 'agendamento_marcelo_status_parecer',
            'agendamento_segundo_passo_obs_parecer' => 'agendamento_marcelo_obs_parecer',
            
            // TERCEIRO PASSO volta para MAURO
            'agendamento_terceiro_passo_data_pagamento_assistente' => 'agendamento_mauro_data_pagamento_assistente',
            'agendamento_terceiro_passo_numero_pedido' => 'agendamento_mauro_numero_pedido',
            'agendamento_terceiro_passo_numero_nota_fiscal' => 'agendamento_mauro_numero_nota_fiscal',
            'agendamento_terceiro_passo_numero_boleto' => 'agendamento_mauro_numero_boleto',
            'agendamento_terceiro_passo_data_envio' => 'agendamento_mauro_data_envio',
            'agendamento_terceiro_passo_data_vencimento' => 'agendamento_mauro_data_vencimento',
            'agendamento_terceiro_passo_status_pagamento' => 'agendamento_mauro_status_pagamento'
        ];
        
        foreach ($renames as $newName => $oldName) {
            $checkSql = "SELECT id FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute([':nome' => $newName]);
            
            if ($stmt->rowCount() > 0) {
                $checkOldSql = "SELECT id FROM permissoes WHERE nome = :nome";
                $stmtOld = $this->conn->prepare($checkOldSql);
                $stmtOld->execute([':nome' => $oldName]);
                
                if ($stmtOld->rowCount() == 0) {
                    $updateSql = "UPDATE permissoes SET nome = :old_name WHERE nome = :new_name";
                    $updateStmt = $this->conn->prepare($updateSql);
                    $updateStmt->execute([
                        ':old_name' => $oldName,
                        ':new_name' => $newName
                    ]);
                    
                    // O relacionamento será mantido automaticamente pois apenas renomeamos a permissão
                }
            }
        }
        
        // Reverte os grupos
        $grupos = [
            'agendamento_clovis_' => 'Agendamentos - CLOVIS',
            'agendamento_marcelo_' => 'Agendamentos - MARCELO',
            'agendamento_mauro_' => 'Agendamentos - MAURO'
        ];
        
        foreach ($grupos as $prefix => $grupo) {
            $updateGrupoSql = "UPDATE permissoes SET grupo = :grupo WHERE nome LIKE :prefix";
            $updateGrupoStmt = $this->conn->prepare($updateGrupoSql);
            $updateGrupoStmt->execute([
                ':grupo' => $grupo,
                ':prefix' => $prefix . '%'
            ]);
        }
    }
}

