/**
 * DataTable específico para Quesitos
 * Configuração e inicialização do DataTable de quesitos com AJAX
 */

(function() {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    let dataTableInstance = null;
    let filterForm = null;

    function init() {
        const table = document.getElementById('datatable-quesitos');
        if (!table) return;

        if (typeof DataTableAjax === 'undefined') {
            console.error('DataTableAjax não está carregado. Certifique-se de incluir datatables-ajax.js antes deste arquivo.');
            return;
        }

        const columns = [
            { data: 0, name: 'data', orderable: true, searchable: false },
            { data: 1, name: 'tipo', orderable: true, searchable: true },
            { data: 2, name: 'vara', orderable: true, searchable: true },
            { data: 3, name: 'reclamante', orderable: true, searchable: true },
            { data: 4, name: 'reclamada', orderable: true, searchable: true },
            { data: 5, name: 'status', orderable: true, searchable: false },
            { data: 6, name: 'email_cliente', orderable: false, searchable: false, className: 'text-center' },
            { data: 7, name: 'acoes', orderable: false, searchable: false, className: 'text-center' }
        ];

        const config = {
            ajaxUrl: window.DOMAIN + '/quesitos/datatable',
            columns: columns,
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            customFilters: getFiltersFromForm(),
            onDraw: function() {
                // Inicializar tooltips após desenhar a tabela
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                }
            },
            onError: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao carregar os dados da tabela.'
                });
            }
        };

        dataTableInstance = new DataTableAjax('datatable-quesitos', config);
        dataTableInstance.init();

        setupFilterForm();
    }

    function getFiltersFromForm() {
        const form = document.querySelector('form[method="GET"]');
        if (!form) return {};

        const formData = new FormData(form);
        const filters = {};

        const status = formData.get('status');
        if (status) filters.status = status;

        const dataInicio = formData.get('data_inicio');
        if (dataInicio) filters.data_inicio = dataInicio;

        const dataFim = formData.get('data_fim');
        if (dataFim) filters.data_fim = dataFim;

        const tipo = formData.get('tipo');
        if (tipo) filters.tipo = tipo;

        const vara = formData.get('vara');
        if (vara) filters.vara = vara;

        const reclamante = formData.get('reclamante');
        if (reclamante) filters.reclamante = reclamante;

        const reclamada = formData.get('reclamada');
        if (reclamada) filters.reclamada = reclamada;

        return filters;
    }

    function setupFilterForm() {
        filterForm = document.querySelector('form[method="GET"]');
        if (!filterForm) return;

        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (dataTableInstance) {
                dataTableInstance.updateFilters(getFiltersFromForm());
            }
        });
    }

})();

