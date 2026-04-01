<?php

namespace Agencia\Close\Migrations;

/**
 * Reaplica a varredura global de mojibake após correção da lógica em
 * Utf8MojibakeFixer (primeira migration FixAllTablesUtf8Mojibake não alterava
 * textos do tipo "Ã³rgÃ£o", "informaÃ§Ãµes").
 */
class FixUtf8MojibakeSecondPass extends FixAllTablesUtf8Mojibake
{
}
