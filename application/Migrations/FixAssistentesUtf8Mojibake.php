<?php

namespace Agencia\Close\Migrations;

/**
 * Corrige mojibake em assistentes (UTF-8 lido como latin1 e gravado de novo).
 * A conversão pura em SQL pode gerar UTF-8 inválido em registros mistos; aqui
 * só atualiza quando o resultado passa em validação UTF-8.
 */
class FixAssistentesUtf8Mojibake extends Migration
{
    private const TEXT_COLUMNS = [
        'nome',
        'nome_contato',
        'email_contato',
        'telefone_contato',
        'profissao',
        'credencial',
        'numero_credencial',
        'cidade_estado',
    ];

    public function up(): void
    {
        if (!$this->tableExists('assistentes')) {
            return;
        }

        $columns = array_values(array_filter(
            self::TEXT_COLUMNS,
            fn (string $c) => $this->columnExists('assistentes', $c)
        ));

        if ($columns === []) {
            return;
        }

        // Caractere Ã (U+00C3) em UTF-8 — indício comum de mojibake em português
        $likePattern = '%' . "\xC3\x83" . '%';

        $selectList = '`id`, `' . implode('`, `', $columns) . '`';
        $whereParts = array_map(static fn (string $c) => "`{$c}` LIKE ?", $columns);
        $sql = 'SELECT ' . $selectList . ' FROM `assistentes` WHERE ' . implode(' OR ', $whereParts);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_fill(0, count($columns), $likePattern));

        $updateStmtTemplate = 'UPDATE `assistentes` SET `%s` = ? WHERE `id` = ?';

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $id = (int) $row['id'];
            foreach ($columns as $col) {
                $val = $row[$col];
                if ($val === null || $val === '') {
                    continue;
                }
                $fixed = $this->tryFixMojibake((string) $val);
                if ($fixed === null) {
                    continue;
                }
                $uq = $this->conn->prepare(sprintf($updateStmtTemplate, $col));
                $uq->execute([$fixed, $id]);
            }
        }
    }

    /**
     * Inverte o padrão clássico de double-encoding (equivalente ao CONVERT MySQL),
     * ou null se não for seguro gravar o resultado.
     */
    private function tryFixMojibake(string $value): ?string
    {
        if (!str_contains($value, "\xC3\x83")) {
            return null;
        }

        $asLatin1 = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
        $decoded = mb_convert_encoding($asLatin1, 'UTF-8', 'ISO-8859-1');

        if ($decoded === $value) {
            return null;
        }

        if ($decoded === '' || !mb_check_encoding($decoded, 'UTF-8')) {
            return null;
        }

        if (@preg_match('//u', $decoded) !== 1) {
            return null;
        }

        return $decoded;
    }

    public function down(): void
    {
        // Reversão não é determinística sem backup dos valores originais.
    }
}
