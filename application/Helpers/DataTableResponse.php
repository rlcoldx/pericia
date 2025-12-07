<?php

namespace Agencia\Close\Helpers;

/**
 * Helper para formatar respostas do DataTables Server-Side Processing
 */
class DataTableResponse
{
    /**
     * Formata resposta para DataTables AJAX
     * 
     * @param array $data Dados da tabela
     * @param int $recordsTotal Total de registros sem filtros
     * @param int $recordsFiltered Total de registros com filtros aplicados
     * @param int $draw Número de requisição (contador)
     * @return array
     */
    public static function format(array $data, int $recordsTotal, int $recordsFiltered, int $draw = 1): array
    {
        return [
            'draw' => (int) $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    /**
     * Extrai parâmetros do DataTables da requisição
     * 
     * @return array
     */
    public static function getParams(): array
    {
        $draw = (int) ($_GET['draw'] ?? 1);
        $start = (int) ($_GET['start'] ?? 0);
        $length = (int) ($_GET['length'] ?? 10);
        $search = $_GET['search']['value'] ?? '';
        $orderColumn = (int) ($_GET['order'][0]['column'] ?? 0);
        $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
        $columns = $_GET['columns'] ?? [];

        return [
            'draw' => $draw,
            'start' => $start,
            'length' => $length,
            'search' => $search,
            'order_column' => $orderColumn,
            'order_dir' => strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC',
            'columns' => $columns
        ];
    }

    /**
     * Obtém o nome da coluna para ordenação
     * 
     * @param int $columnIndex Índice da coluna
     * @param array $columns Array de colunas do DataTables
     * @param array $columnMap Mapeamento de índices para nomes de colunas do banco
     * @return string|null
     */
    public static function getOrderColumn(int $columnIndex, array $columns, array $columnMap): ?string
    {
        if (!isset($columns[$columnIndex]) || !isset($columnMap[$columnIndex])) {
            return null;
        }

        return $columnMap[$columnIndex];
    }
}

