<?php

namespace Agencia\Close\Models\Tarefa;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Models\Model;

class Tarefa extends Model
{
    protected Create $create;
    protected Read $read;
    protected Update $update;

    public function __construct()
    {
        $this->create = new Create();
        $this->read = new Read();
        $this->update = new Update();
    }

    /**
     * Busca tarefa por módulo e registro
     */
    public function getPorModuloRegistro(string $modulo, int $registroId, int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'tarefas',
            'WHERE modulo = :modulo AND registro_id = :registro_id AND empresa = :empresa ORDER BY id DESC LIMIT 1',
            "modulo={$modulo}&registro_id={$registroId}&empresa={$empresa}"
        );
        return $this->read;
    }

    /**
     * Cria uma nova tarefa
     */
    public function criar(array $data): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate('tarefas', $data);
        return $this->create;
    }

    /**
     * Atualiza uma tarefa
     */
    public function atualizar(int $id, int $empresa, array $data): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'tarefas',
            $data,
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->update;
    }

    /**
     * Salva ou atualiza tarefa (upsert)
     */
    public function salvarTarefa(string $modulo, int $registroId, int $empresa, array $dados): bool
    {
        try {
            // Verificar se a tabela existe antes de tentar salvar (silenciosamente)
            try {
                $testRead = new Read();
                $testRead->FullRead("SELECT 1 FROM tarefas LIMIT 1");
            } catch (\Exception $e) {
                // Retorna false mas não lança exceção para não bloquear o cadastro do quesito
                return false;
            } catch (\Error $e) {
                return false;
            }
            
            // Busca tarefa existente
            $tarefaRead = $this->getPorModuloRegistro($modulo, $registroId, $empresa);
            $tarefaExistente = $tarefaRead->getResult()[0] ?? null;

            // Determinar se está concluído (pode vir como true, 1, '1', ou 'on')
            $concluido = false;
            if (isset($dados['concluido'])) {
                $concluido = ($dados['concluido'] === true || 
                             $dados['concluido'] === 1 || 
                             $dados['concluido'] === '1' || 
                             $dados['concluido'] === 'on');
            }
            
            // Se está marcando como concluído e não tem usuário responsável definido, manter o atual ou usar o logado
            $usuarioResponsavelId = null;
            if (!empty($dados['usuario_responsavel_id'])) {
                $usuarioResponsavelId = (int) $dados['usuario_responsavel_id'];
            } elseif ($tarefaExistente && !empty($tarefaExistente['usuario_responsavel_id'])) {
                // Manter o responsável atual se não foi alterado
                $usuarioResponsavelId = (int) $tarefaExistente['usuario_responsavel_id'];
            }

            $dadosTarefa = [
                'empresa' => $empresa,
                'modulo' => $modulo,
                'registro_id' => $registroId,
                'concluido' => $concluido ? 1 : 0,
                'usuario_responsavel_id' => $usuarioResponsavelId,
                'data_conclusao' => !empty($dados['data_conclusao']) ? $dados['data_conclusao'] : null,
                'tarefa_texto' => !empty($dados['tarefa_texto']) ? trim($dados['tarefa_texto']) : null,
                'usuario_concluiu_id' => $concluido ? ($_SESSION['pericia_perfil_id'] ?? null) : null,
            ];

            if ($tarefaExistente) {
                // Atualiza
                $result = $this->atualizar((int) $tarefaExistente['id'], $empresa, $dadosTarefa);
            } else {
                // Cria
                $result = $this->criar($dadosTarefa);
            }

            return (bool) $result->getResult();
        } catch (\Exception $e) {
            return false;
        } catch (\Error $e) {
            return false;
        }
    }

    /**
     * Busca tarefas do usuário logado para DataTable
     */
    public function getTarefasUsuarioDataTable(int $usuarioId, int $empresa, array $params, array $filtros = []): array
    {
        $this->read = new Read();

        // Parâmetros do DataTable
        $start = (int) ($params['start'] ?? 0);
        $length = (int) ($params['length'] ?? 10);
        $search = trim($params['search']['value'] ?? '');

        // WHERE base
        $where = "WHERE t.empresa = :empresa AND t.usuario_responsavel_id = :usuario_id";
        $parseString = "empresa={$empresa}&usuario_id={$usuarioId}";

        // Filtro de status (concluído ou não)
        if (!empty($filtros['status'])) {
            if ($filtros['status'] === 'concluido') {
                $where .= " AND t.concluido = 1";
            } elseif ($filtros['status'] === 'pendente') {
                $where .= " AND t.concluido = 0";
            }
        }

        // Busca geral
        $searchWhere = '';
        if (!empty($search)) {
            $searchWhere = " AND (
                t.modulo LIKE :search OR
                COALESCE(
                    CASE 
                        WHEN t.modulo = 'quesito' THEN (SELECT rd.nome FROM quesitos q LEFT JOIN reclamadas rd ON q.reclamada_id = rd.id AND rd.empresa = q.empresa WHERE q.id = t.registro_id AND q.empresa = t.empresa LIMIT 1)
                        WHEN t.modulo = 'manifestacao' THEN (SELECT rd.nome FROM manifestacoes_impugnacoes m LEFT JOIN reclamadas rd ON m.reclamada_id = rd.id AND rd.empresa = m.empresa WHERE m.id = t.registro_id AND m.empresa = t.empresa LIMIT 1)
                        WHEN t.modulo = 'parecer' THEN (SELECT rd.nome FROM pareceres p LEFT JOIN reclamadas rd ON p.reclamada_id = rd.id AND rd.empresa = p.empresa WHERE p.id = t.registro_id AND p.empresa = t.empresa LIMIT 1)
                        WHEN t.modulo = 'agendamento' THEN (SELECT a.cliente_nome FROM agendamentos a WHERE a.id = t.registro_id AND a.empresa = t.empresa LIMIT 1)
                    END,
                    ''
                ) LIKE :search
            )";
            $parseString .= "&search=%{$search}%";
        }

        // Contagem total
        $sqlTotal = "SELECT COUNT(*) as total FROM tarefas t {$where}";
        $this->read->FullRead($sqlTotal, $parseString);
        $total = (int) ($this->read->getResult()[0]['total'] ?? 0);

        // Contagem filtrada
        $sqlFiltered = "SELECT COUNT(*) as total FROM tarefas t {$where}{$searchWhere}";
        $this->read->FullRead($sqlFiltered, $parseString);
        $filtered = (int) ($this->read->getResult()[0]['total'] ?? 0);

        // Busca dos dados
        $sql = "SELECT 
                    t.id,
                    t.modulo,
                    t.registro_id,
                    t.concluido,
                    t.data_conclusao,
                    t.data_create,
                    COALESCE(
                        CASE 
                            WHEN t.modulo = 'quesito' THEN (SELECT rd.nome FROM quesitos q LEFT JOIN reclamadas rd ON q.reclamada_id = rd.id AND rd.empresa = q.empresa WHERE q.id = t.registro_id AND q.empresa = t.empresa LIMIT 1)
                            WHEN t.modulo = 'manifestacao' THEN (SELECT rd.nome FROM manifestacoes_impugnacoes m LEFT JOIN reclamadas rd ON m.reclamada_id = rd.id AND rd.empresa = m.empresa WHERE m.id = t.registro_id AND m.empresa = t.empresa LIMIT 1)
                            WHEN t.modulo = 'parecer' THEN (SELECT rd.nome FROM pareceres p LEFT JOIN reclamadas rd ON p.reclamada_id = rd.id AND rd.empresa = p.empresa WHERE p.id = t.registro_id AND p.empresa = t.empresa LIMIT 1)
                            WHEN t.modulo = 'agendamento' THEN (SELECT a.cliente_nome FROM agendamentos a WHERE a.id = t.registro_id AND a.empresa = t.empresa LIMIT 1)
                        END,
                        'Sem Reclamada'
                    ) as reclamada,
                    CASE 
                        WHEN t.modulo = 'manifestacao' THEN (SELECT m.favoravel FROM manifestacoes_impugnacoes m WHERE m.id = t.registro_id AND m.empresa = t.empresa LIMIT 1)
                        ELSE NULL
                    END as favoravel
                FROM tarefas t
                {$where}{$searchWhere}
                ORDER BY t.data_create DESC
                LIMIT :limit OFFSET :offset";

        $parseString .= "&limit={$length}&offset={$start}";
        $this->read->FullRead($sql, $parseString);

        $resultData = $this->read->getResult() ?? [];
        
        // Garantir que sempre retornamos um array, mesmo que vazio
        if (!is_array($resultData)) {
            $resultData = [];
        }

        return [
            'data' => $resultData,
            'total' => $total,
            'filtered' => $filtered,
        ];
    }
}
