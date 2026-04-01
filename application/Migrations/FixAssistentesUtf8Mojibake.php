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

        $likePattern = Utf8MojibakeFixer::likePattern();

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
                $fixed = Utf8MojibakeFixer::tryFix((string) $val);
                if ($fixed === null) {
                    continue;
                }
                $uq = $this->conn->prepare(sprintf($updateStmtTemplate, $col));
                $uq->execute([$fixed, $id]);
            }
        }
    }

    public function down(): void
    {
        // Reversão não é determinística sem backup dos valores originais.
    }
}
