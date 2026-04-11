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
            { data: 2, name: 'tipo_trabalho', orderable: true, searchable: true },
            { data: 3, name: 'vara', orderable: true, searchable: true },
            { data: 4, name: 'reclamante', orderable: true, searchable: true },
            { data: 5, name: 'reclamada', orderable: true, searchable: true },
            { data: 6, name: 'status', orderable: true, searchable: false },
            { data: 7, name: 'email_cliente', orderable: false, searchable: false, className: 'text-center' },
            { data: 8, name: 'acoes', orderable: false, searchable: false, className: 'text-center text-nowrap' }
        ];

        const config = {
            ajaxUrl: window.DOMAIN + '/quesitos/datatable',
            columns: columns,
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            customConfig: {
                responsive: false,
                autoWidth: false,
                ajax: {
                    data: function(d) {
                        const f = getFiltersFromForm();
                        Object.keys(f).forEach(function(key) {
                            d[key] = f[key];
                        });
                        return d;
                    }
                }
            },
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
        bindExcluirQuesito();
    }

    function bindExcluirQuesito() {
        if (bindExcluirQuesito._done) {
            return;
        }
        bindExcluirQuesito._done = true;

        document.body.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-excluir-quesito');
            if (!btn) {
                return;
            }
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            if (!id || typeof Swal === 'undefined') {
                return;
            }

            Swal.fire({
                title: 'Excluir quesito?',
                text: 'Somente este registro será removido. Nenhum outro módulo será alterado.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }
                const fd = new FormData();
                fd.append('id', id);
                fetch(window.DOMAIN + '/quesitos/excluir', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            Swal.fire('Excluído', data.message || '', 'success');
                            if (dataTableInstance) {
                                dataTableInstance.reload();
                            }
                        } else {
                            Swal.fire('Erro', data.message || 'Não foi possível excluir.', 'error');
                        }
                    })
                    .catch(function () {
                        Swal.fire('Erro', 'Falha na requisição.', 'error');
                    });
            });
        });
    }

    function getFiltersFromForm() {
        const form = document.getElementById('filtrosQuesito');
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
        filterForm = document.getElementById('filtrosQuesito');
        if (!filterForm) return;

        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (dataTableInstance) {
                dataTableInstance.reload();
            }
        });
    }

})();

