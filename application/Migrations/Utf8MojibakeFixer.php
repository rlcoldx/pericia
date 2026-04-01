<?php

namespace Agencia\Close\Migrations;

/**
 * Corrige mojibake de UTF-8 (ex.: Ã³ → ó, informaÃ§Ãµes → informações).
 *
 * O caso mais comum em PHP/PDO é uma ou mais camadas onde o texto já é UTF-8
 * válido, mas cada sequência UTF-8 foi tratada como caracteres Latin-1 e
 * voltou a ser codificada em UTF-8. Equivale a utf8_decode em cada camada:
 * mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8') até estabilizar.
 *
 * Mantém fallback com a fórmula antiga (UTF-8 → Latin-1 → UTF-8 em dois
 * sentidos) para variantes de dump/conversão.
 */
final class Utf8MojibakeFixer
{
    private const MAX_LAYERS = 8;

    private function __construct()
    {
    }

    public static function likePattern(): string
    {
        return '%' . "\xC3\x83" . '%';
    }

    public static function tryFix(string $value): ?string
    {
        if (strpos($value, "\xC3\x83") === false) {
            return null;
        }

        $cur = $value;
        for ($i = 0; $i < self::MAX_LAYERS; $i++) {
            if (strpos($cur, "\xC3\x83") === false) {
                break;
            }
            $next = mb_convert_encoding($cur, 'ISO-8859-1', 'UTF-8');
            if (
                $next === $cur
                || !mb_check_encoding($next, 'UTF-8')
                || @preg_match('//u', $next) !== 1
            ) {
                break;
            }
            $cur = $next;
        }

        if ($cur !== $value) {
            return $cur;
        }

        $asLatin1 = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
        $decoded = mb_convert_encoding($asLatin1, 'UTF-8', 'ISO-8859-1');

        if (
            $decoded === $value
            || $decoded === ''
            || !mb_check_encoding($decoded, 'UTF-8')
            || @preg_match('//u', $decoded) !== 1
        ) {
            return null;
        }

        return $decoded;
    }
}
