/**
 * DataTable específico para Pareceres
 * Configuração e inicialização do DataTable com AJAX
 */

(function() {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    let dataTableInstance = null;

    function init() {
        const table = document.getElementById('datatable-pareceres');
        if (!table) return;

        if (typeof DataTableAjax === 'undefined') {
            console.error('DataTableAjax não está carregado. Certifique-se de incluir datatables-ajax.js antes deste arquivo.');
            return;
        }

        const columns = [
            { data: 0, name: 'data_realizacao', orderable: true, searchable: false },
            { data: 1, name: 'data_fatal', orderable: true, searchable: false },
            { data: 2, name: 'tipo', orderable: true, searchable: true },
            { data: 3, name: 'assistente', orderable: true, searchable: true },
            { data: 4, name: 'reclamada', orderable: true, searchable: true },
            { data: 5, name: 'reclamante', orderable: true, searchable: true },
            { data: 6, name: 'acoes', orderable: false, searchable: false, className: 'text-center' }
        ];

        const config = {
            ajaxUrl: window.DOMAIN + '/pareceres/datatable',
            columns: columns,
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            customFilters: {},
            onDraw: function() {},
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
    }

})();
