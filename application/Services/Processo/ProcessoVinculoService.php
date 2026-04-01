<?php

namespace Agencia\Close\Services\Processo;

use Agencia\Close\Conn\Conn;
use Agencia\Close\Conn\Read;
use PDO;

/**
 * Busca dados de vínculo (reclamada/reclamante e campos auxiliares) pelo número do processo
 * em agendamentos, quesitos, manifestações e pareceres da mesma empresa.
 */
class ProcessoVinculoService extends Conn
{
    /**
     * Números de processo já usados na empresa (todos os módulos que armazenam o vínculo).
     *
     * @return list<string>
     */
    public function listarNumerosProcessoDistintos(int $empresa): array
    {
        $collected = [];
        $queries = [
            "SELECT DISTINCT TRIM(numero_processo) AS n FROM agendamentos WHERE empresa = :e AND numero_processo IS NOT NULL AND TRIM(numero_processo) <> ''",
            "SELECT DISTINCT TRIM(numero_processo) AS n FROM quesitos WHERE empresa = :e AND numero_processo IS NOT NULL AND TRIM(numero_processo) <> ''",
            "SELECT DISTINCT TRIM(numero_processo) AS n FROM pareceres WHERE empresa = :e AND numero_processo IS NOT NULL AND TRIM(numero_processo) <> ''",
            "SELECT DISTINCT TRIM(numero) AS n FROM manifestacoes_impugnacoes WHERE empresa = :e AND numero IS NOT NULL AND TRIM(numero) <> ''",
            "SELECT DISTINCT TRIM(numero_processo) AS n FROM contas_receber WHERE empresa = :e AND numero_processo IS NOT NULL AND TRIM(numero_processo) <> ''",
        ];
        foreach ($queries as $sql) {
            $read = new Read();
            $read->FullRead($sql, 'e=' . $empresa);
            foreach ($read->getResult() ?? [] as $row) {
                if (!empty($row['n'])) {
                    $collected[] = (string) $row['n'];
                }
            }
        }
        $collected = array_values(array_unique($collected));
        sort($collected, SORT_NATURAL | SORT_FLAG_CASE);

        return $collected;
    }

    private const FONTE_AGENDAMENTO = 'agendamento';
    private const FONTE_QUESITO = 'quesito';
    private const FONTE_MANIFESTACAO = 'manifestacao';
    private const FONTE_PARECER = 'parecer';

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorNumero(
        int $empresa,
        string $numero,
        ?string $excludeFonte = null,
        ?int $excludeId = null
    ): ?array {
        $numero = trim($numero);
        if ($numero === '') {
            return null;
        }

        $candidatos = [
            fn () => $this->buscarEmAgendamentos($empresa, $numero),
            fn () => $this->buscarEmQuesitos($empresa, $numero),
            fn () => $this->buscarEmManifestacoes($empresa, $numero),
            fn () => $this->buscarEmPareceres($empresa, $numero),
        ];

        foreach ($candidatos as $fn) {
            $row = $fn();
            if ($row === null) {
                continue;
            }
            if ($excludeFonte !== null && $excludeId !== null
                && ($row['fonte'] ?? '') === $excludeFonte
                && (int) ($row['registro_id'] ?? 0) === $excludeId) {
                continue;
            }
            return $row;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buscarEmAgendamentos(int $empresa, string $numero): ?array
    {
        $read = new Read();
        $read->FullRead(
            "SELECT id, cliente_nome, reclamante_nome, vara, perito_id, assistente_id
             FROM agendamentos
             WHERE empresa = :empresa
               AND numero_processo IS NOT NULL
               AND TRIM(numero_processo) = :np
             ORDER BY id DESC
             LIMIT 1",
            'empresa=' . $empresa . '&np=' . rawurlencode($numero)
        );
        $rows = $read->getResult();
        if (empty($rows[0])) {
            return null;
        }
        $a = $rows[0];
        $nomeRec = isset($a['cliente_nome']) ? trim((string) $a['cliente_nome']) : '';
        $nomeRecl = isset($a['reclamante_nome']) ? trim((string) $a['reclamante_nome']) : '';

        $recId = $nomeRec !== '' ? $this->idReclamadaPorNome($empresa, $nomeRec) : null;
        $reclId = $nomeRecl !== '' ? $this->idReclamantePorNome($empresa, $nomeRecl) : null;

        return [
            'fonte' => self::FONTE_AGENDAMENTO,
            'registro_id' => (int) $a['id'],
            'reclamada_id' => $recId,
            'reclamante_id' => $reclId,
            'reclamada_nome' => $nomeRec !== '' ? $nomeRec : null,
            'reclamante_nome' => $nomeRecl !== '' ? $nomeRecl : null,
            'vara' => isset($a['vara']) && $a['vara'] !== '' && $a['vara'] !== null ? trim((string) $a['vara']) : null,
            'perito_id' => !empty($a['perito_id']) ? (int) $a['perito_id'] : null,
            'assistente_id' => !empty($a['assistente_id']) ? (int) $a['assistente_id'] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buscarEmQuesitos(int $empresa, string $numero): ?array
    {
        $read = new Read();
        $read->FullRead(
            "SELECT id, reclamada_id, reclamante_id, vara
             FROM quesitos
             WHERE empresa = :empresa
               AND numero_processo IS NOT NULL
               AND TRIM(numero_processo) = :np
             ORDER BY id DESC
             LIMIT 1",
            'empresa=' . $empresa . '&np=' . rawurlencode($numero)
        );
        $rows = $read->getResult();
        if (empty($rows[0])) {
            return null;
        }
        $q = $rows[0];
        return $this->montarRespostaIds(
            self::FONTE_QUESITO,
            (int) $q['id'],
            !empty($q['reclamada_id']) ? (int) $q['reclamada_id'] : null,
            !empty($q['reclamante_id']) ? (int) $q['reclamante_id'] : null,
            isset($q['vara']) ? trim((string) $q['vara']) : null
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buscarEmManifestacoes(int $empresa, string $numero): ?array
    {
        $read = new Read();
        $read->FullRead(
            "SELECT id, reclamada_id, reclamante_id, perito_id
             FROM manifestacoes_impugnacoes
             WHERE empresa = :empresa
               AND numero IS NOT NULL
               AND TRIM(numero) = :np
             ORDER BY id DESC
             LIMIT 1",
            'empresa=' . $empresa . '&np=' . rawurlencode($numero)
        );
        $rows = $read->getResult();
        if (empty($rows[0])) {
            return null;
        }
        $m = $rows[0];
        $out = $this->montarRespostaIds(
            self::FONTE_MANIFESTACAO,
            (int) $m['id'],
            !empty($m['reclamada_id']) ? (int) $m['reclamada_id'] : null,
            !empty($m['reclamante_id']) ? (int) $m['reclamante_id'] : null,
            null
        );
        $out['perito_id'] = !empty($m['perito_id']) ? (int) $m['perito_id'] : null;
        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buscarEmPareceres(int $empresa, string $numero): ?array
    {
        $read = new Read();
        $read->FullRead(
            "SELECT id, reclamada_id, reclamante_id, assistente_id
             FROM pareceres
             WHERE empresa = :empresa
               AND numero_processo IS NOT NULL
               AND TRIM(numero_processo) = :np
             ORDER BY id DESC
             LIMIT 1",
            'empresa=' . $empresa . '&np=' . rawurlencode($numero)
        );
        $rows = $read->getResult();
        if (empty($rows[0])) {
            return null;
        }
        $p = $rows[0];
        $out = $this->montarRespostaIds(
            self::FONTE_PARECER,
            (int) $p['id'],
            !empty($p['reclamada_id']) ? (int) $p['reclamada_id'] : null,
            !empty($p['reclamante_id']) ? (int) $p['reclamante_id'] : null,
            null
        );
        $out['assistente_id'] = !empty($p['assistente_id']) ? (int) $p['assistente_id'] : null;
        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function montarRespostaIds(
        string $fonte,
        int $registroId,
        ?int $reclamadaId,
        ?int $reclamanteId,
        ?string $vara
    ): array {
        $recNome = null;
        $reclNome = null;
        if ($reclamadaId) {
            $recNome = $this->nomeReclamadaPorId($reclamadaId);
        }
        if ($reclamanteId) {
            $reclNome = $this->nomeReclamantePorId($reclamanteId);
        }

        return [
            'fonte' => $fonte,
            'registro_id' => $registroId,
            'reclamada_id' => $reclamadaId,
            'reclamante_id' => $reclamanteId,
            'reclamada_nome' => $recNome,
            'reclamante_nome' => $reclNome,
            'vara' => $vara,
            'perito_id' => null,
            'assistente_id' => null,
        ];
    }

    private function idReclamadaPorNome(int $empresa, string $nome): ?int
    {
        $read = new Read();
        $read->FullRead(
            "SELECT id FROM reclamadas WHERE empresa = :empresa AND TRIM(nome) = :nome LIMIT 1",
            'empresa=' . $empresa . '&nome=' . rawurlencode(trim($nome))
        );
        $rows = $read->getResult();
        return !empty($rows[0]['id']) ? (int) $rows[0]['id'] : null;
    }

    private function idReclamantePorNome(int $empresa, string $nome): ?int
    {
        $read = new Read();
        $read->FullRead(
            "SELECT id FROM reclamantes WHERE empresa = :empresa AND TRIM(nome) = :nome LIMIT 1",
            'empresa=' . $empresa . '&nome=' . rawurlencode(trim($nome))
        );
        $rows = $read->getResult();
        return !empty($rows[0]['id']) ? (int) $rows[0]['id'] : null;
    }

    private function nomeReclamadaPorId(int $id): ?string
    {
        $read = new Read();
        $read->FullRead(
            "SELECT nome FROM reclamadas WHERE id = :id LIMIT 1",
            'id=' . $id
        );
        $rows = $read->getResult();
        if (empty($rows[0]['nome'])) {
            return null;
        }
        return trim((string) $rows[0]['nome']);
    }

    private function nomeReclamantePorId(int $id): ?string
    {
        $read = new Read();
        $read->FullRead(
            "SELECT nome FROM reclamantes WHERE id = :id LIMIT 1",
            'id=' . $id
        );
        $rows = $read->getResult();
        if (empty($rows[0]['nome'])) {
            return null;
        }
        return trim((string) $rows[0]['nome']);
    }
}
