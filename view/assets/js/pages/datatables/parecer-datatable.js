/**
 * DataTable específico para Pareceres
 */

(function() {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    let dataTableInstance = null;

    function renderHtml(data) {
        return data == null ? '' : data;
    }

    function init() {
        const table = document.getElementById('datatable-pareceres');
        if (!table) return;

        if (typeof DataTableAjax === 'undefined') {
            console.error('DataTableAjax não está carregado.');
            return;
        }

        const columns = [
            { data: 'status', name: 'status', orderable: true, searchable: false, render: renderHtml },
            { data: 'data_realizacao', name: 'data_realizacao', orderable: true, searchable: false, render: renderHtml },
            { data: 'data_fatal', name: 'data_fatal', orderable: true, searchable: false, render: renderHtml },
            { data: 'tipo', name: 'tipo', orderable: true, searchable: true, render: renderHtml },
            { data: 'assistente', name: 'assistente', orderable: true, searchable: true, render: renderHtml },
            { data: 'reclamada', name: 'reclamada', orderable: true, searchable: true, render: renderHtml },
            { data: 'reclamante', name: 'reclamante', orderable: true, searchable: true, render: renderHtml },
            { data: 'acoes', name: 'acoes', orderable: false, searchable: false, render: renderHtml, className: 'text-center text-nowrap' }
        ];

        const config = {
            ajaxUrl: window.DOMAIN + '/pareceres/datatable',
            columns: columns,
            order: [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            customFilters: {},
            customConfig: {
                responsive: false,
                autoWidth: false
            },
            onError: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao carregar os dados da tabela.'
                });
            }
        };

        dataTableInstance = new DataTableAjax('datatable-pareceres', config);
        dataTableInstance.init();
        setupExportButton();
    }

    function setupExportButton() {
        const btn = document.getElementById('btnExportarPareceresExcel');
        if (!btn) return;

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const base = (window.DOMAIN || '').replace(/\/$/, '');
            const params = new URLSearchParams();
            try {
                const api = dataTableInstance && dataTableInstance.table
                    ? dataTableInstance.table
                    : null;
                if (api && typeof api.search === 'function') {
                    const q = api.search();
                    if (q) params.append('search', q);
                }
            } catch (err) {}

            const qs = params.toString();
            window.location.href = base + '/pareceres/exportar' + (qs ? ('?' + qs) : '');
        });
    }
})();
