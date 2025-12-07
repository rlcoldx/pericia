# Guia de Implementação - DataTables AJAX (Server-Side Processing)

Este guia explica como implementar DataTables com AJAX remoto em novos módulos do sistema.

## Estrutura Criada

### Backend

1. **`application/Helpers/DataTableResponse.php`**
   - Classe helper para formatar respostas JSON do DataTables
   - Métodos: `format()`, `getParams()`, `getOrderColumn()`

2. **Model** - Adicionar método `get[Nome]DataTable()`
   - Recebe parâmetros do DataTables (start, length, search, order)
   - Retorna array com `['data' => [], 'total' => int, 'filtered' => int]`

3. **Controller** - Adicionar método `datatable()`
   - Extrai parâmetros usando `DataTableResponse::getParams()`
   - Chama método do Model
   - Formata células HTML usando métodos privados `format*Cell()`
   - Retorna JSON usando `DataTableResponse::format()`

### Frontend

1. **`view/assets/js/datatables/datatables-ajax.js`**
   - Classe base `DataTableAjax` reutilizável
   - Configuração padrão para todos os DataTables AJAX

2. **`view/assets/js/pages/datatables/[modulo]-datatable.js`**
   - Configuração específica do módulo
   - Define colunas, filtros, callbacks

## Passo a Passo para Novo Módulo

### 1. Backend - Model

Adicione método no Model:

```php
public function get[Nome]DataTable(int $company, array $params, array $filtros = []): array
{
    $start = $params['start'] ?? 0;
    $length = $params['length'] ?? 10;
    $search = $params['search'] ?? '';
    $orderColumn = $params['order_column'] ?? 0;
    $orderDir = $params['order_dir'] ?? 'ASC';

    // Mapeamento de colunas
    $columnMap = [
        0 => 'campo1',
        1 => 'campo2',
        // ...
    ];

    $orderBy = 'campo_padrao DESC';
    if (isset($columnMap[$orderColumn])) {
        $orderBy = $columnMap[$orderColumn] . ' ' . $orderDir;
    }

    // WHERE base
    $where = "WHERE empresa = :empresa";
    $whereParams = "empresa={$company}";

    // Filtros adicionais
    // ...

    // Busca global
    $searchWhere = $where;
    $searchParams = $whereParams;
    if (!empty($search)) {
        $searchWhere .= " AND (campo1 LIKE :search OR campo2 LIKE :search)";
        $searchParams .= "&search=%{$search}%";
    }

    // Conta total
    $this->read = new Read();
    $this->read->ExeRead("tabela", $where, $whereParams);
    $totalRecords = $this->read->getRowCount();

    // Conta filtrado
    $this->read = new Read();
    $this->read->ExeRead("tabela", $searchWhere, $searchParams);
    $filteredRecords = $this->read->getRowCount();

    // Busca dados paginados
    $this->read = new Read();
    $limitClause = "LIMIT :limit OFFSET :offset";
    $finalParams = $searchParams . "&limit={$length}&offset={$start}";
    $this->read->ExeRead("tabela", $searchWhere . " ORDER BY " . $orderBy . " " . $limitClause, $finalParams);
    
    return [
        'data' => $this->read->getResult() ?? [],
        'total' => $totalRecords,
        'filtered' => $filteredRecords
    ];
}
```

### 2. Backend - Controller

Adicione método no Controller:

```php
use Agencia\Close\Helpers\DataTableResponse;

public function datatable($params)
{
    $this->setParams($params);
    $this->requirePermission('permissao_ver');
    
    $empresa = $_SESSION['pericia_perfil_empresa'] ?? null;
    if (!$empresa) {
        $this->responseJson(DataTableResponse::format([], 0, 0, $_GET['draw'] ?? 1));
        return;
    }

    $dtParams = DataTableResponse::getParams();
    
    // Filtros do formulário
    $filtros = [];
    // ...

    $model = new SeuModel();
    $result = $model->get[Nome]DataTable($empresa, $dtParams, $filtros);
    
    // Formata dados
    $formattedData = [];
    foreach ($result['data'] as $item) {
        $formattedData[] = [
            $this->formatCampo1Cell($item),
            $this->formatCampo2Cell($item),
            // ...
        ];
    }

    $response = DataTableResponse::format(
        $formattedData,
        $result['total'],
        $result['filtered'],
        $dtParams['draw']
    );

    $this->responseJson($response);
}

// Métodos privados para formatar células
private function formatCampo1Cell($item): string { /* ... */ }
private function formatCampo2Cell($item): string { /* ... */ }
```

### 3. Backend - Rota

Adicione rota:

```php
$router->get("/modulo/datatable", "ModuloController:datatable");
```

### 4. Frontend - JavaScript

Crie arquivo `view/assets/js/pages/datatables/[modulo]-datatable.js`:

```javascript
(function() {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    let dataTableInstance = null;

    function init() {
        const table = document.getElementById('datatable-[modulo]');
        if (!table || typeof DataTableAjax === 'undefined') return;

        const columns = [
            { data: 0, name: 'campo1', orderable: true, searchable: true },
            { data: 1, name: 'campo2', orderable: true, searchable: false },
            // ...
        ];

        const config = {
            ajaxUrl: window.DOMAIN + '/modulo/datatable',
            columns: columns,
            order: [[1, 'desc']],
            pageLength: 10,
            customFilters: getFiltersFromForm(),
            onDraw: function(settings) {
                // Reinicializa tooltips
                if (typeof bootstrap !== 'undefined') {
                    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
                }
            }
        };

        dataTableInstance = new DataTableAjax('datatable-[modulo]', config);
        dataTableInstance.init();

        setupFilterForm();
    }

    function getFiltersFromForm() {
        const form = document.getElementById('filtrosModulo');
        if (!form) return {};
        
        const formData = new FormData(form);
        const filters = {};
        // Extrai filtros...
        return filters;
    }

    function setupFilterForm() {
        const form = document.getElementById('filtrosModulo');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (dataTableInstance) {
                dataTableInstance.updateFilters(getFiltersFromForm());
            }
        });
    }
})();
```

### 5. Frontend - View

Atualize a view Twig:

```twig
<!-- Filtros -->
<form id="filtrosModulo">
    <!-- Campos de filtro -->
</form>

<!-- Tabela -->
<table id="datatable-[modulo]" class="table dataTable">
    <thead>
        <tr>
            <th>Coluna 1</th>
            <th>Coluna 2</th>
            <!-- ... -->
        </tr>
    </thead>
    <tbody>
        <!-- Dados carregados via AJAX -->
    </tbody>
</table>

{% block scripts %}
<script>
    window.DOMAIN = '{{ DOMAIN }}';
</script>
<script src="{{ PATH }}/view/assets/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="{{ PATH }}/view/assets/js/datatables/datatables-ajax.js"></script>
<script src="{{ PATH }}/view/assets/js/pages/datatables/[modulo]-datatable.js"></script>
{% endblock %}
```

## Exemplo Completo - Agendamentos

Veja a implementação completa em:
- Model: `application/Models/Agendamento/Agendamento.php` - método `getAgendamentosDataTable()`
- Controller: `application/Controllers/Agendamento/AgendamentoController.php` - método `datatable()`
- JavaScript: `view/assets/js/pages/datatables/agendamento-datatable.js`
- View: `view/pages/agendamento/index.twig`

## Vantagens

1. **Performance**: Carrega apenas dados necessários (paginação server-side)
2. **Escalabilidade**: Funciona com milhares de registros
3. **Reutilizável**: Classe base pode ser usada em qualquer módulo
4. **Organizado**: Código separado e bem estruturado
5. **Manutenível**: Fácil de atualizar e debugar

## Notas Importantes

- Sempre use `DataTableResponse::format()` para garantir formato correto
- O array `data` retornado deve ter o mesmo número de elementos que as colunas definidas
- Use `htmlspecialchars()` ao formatar células HTML para segurança
- Filtros customizados são enviados via `customFilters` no JavaScript
- A busca global funciona em todos os campos `searchable: true`

