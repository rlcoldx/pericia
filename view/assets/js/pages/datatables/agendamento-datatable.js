/**
 * DataTable específico para Agendamentos
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
        const table = document.getElementById('datatable-agendamentos');
        if (!table) return;

        if (typeof DataTableAjax === 'undefined') {
            console.error('DataTableAjax não está carregado.');
            return;
        }

        const columns = [
            { data: 'status', name: 'status', orderable: true, searchable: false, render: renderHtml },
            { data: 'data_hora', name: 'data_agendamento', orderable: true, searchable: false, render: renderHtml },
            { data: 'tipo', name: 'tipo_pericia', orderable: true, searchable: true, render: renderHtml },
            { data: 'reclamada', name: 'cliente_nome', orderable: true, searchable: true, render: renderHtml },
            { data: 'reclamante', name: 'reclamante_nome', orderable: true, searchable: true, render: renderHtml },
            { data: 'assistente', name: 'assistente_nome', orderable: true, searchable: true, render: renderHtml },
            { data: 'perito', name: 'perito_id', orderable: true, searchable: false, render: renderHtml },
            { data: 'local', name: 'local_pericia', orderable: false, searchable: true, render: renderHtml, className: 'align-top' },
            { data: 'acoes', name: 'acoes', orderable: false, searchable: false, render: renderHtml, className: 'text-center text-nowrap' }
        ];

        const config = {
            ajaxUrl: window.DOMAIN + '/agendamento/datatable',
            columns: columns,
            order: [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            customFilters: getFiltersFromForm(),
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

        dataTableInstance = new DataTableAjax('datatable-agendamentos', config);
        dataTableInstance.init();
        setupFilterForm();
    }

    function getFiltersFromForm() {
        const form = document.getElementById('filtrosAgendamento');
        if (!form) return {};

        const formData = new FormData(form);
        const filters = {};

        const status = formData.get('status');
        if (status) filters.status = status;

        const peritoId = formData.get('perito_id');
        if (peritoId) filters.perito_id = peritoId;

        const dataInicio = formData.get('data_inicio');
        if (dataInicio) filters.data_inicio = dataInicio;

        const dataFim = formData.get('data_fim');
        if (dataFim) filters.data_fim = dataFim;

        return filters;
    }

    function setupFilterForm() {
        const filterForm = document.getElementById('filtrosAgendamento');
        if (!filterForm) return;

        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (dataTableInstance) {
                dataTableInstance.updateFilters(getFiltersFromForm());
            }
        });

        const btnLimpar = document.getElementById('btnLimparFiltros');
        if (btnLimpar) {
            btnLimpar.addEventListener('click', function() {
                filterForm.reset();
                if (dataTableInstance) {
                    dataTableInstance.updateFilters({});
                }
            });
        }
    }

    window.removerAgendamento = function(id, nomeCliente) {
        window.agendamentoParaRemover = id;
        const nomeElement = document.getElementById('nomeCliente');
        if (nomeElement) {
            nomeElement.textContent = nomeCliente;
        }
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
    };

    const confirmRemover = document.getElementById('confirmRemover');
    if (confirmRemover) {
        confirmRemover.addEventListener('click', function() {
            if (!window.agendamentoParaRemover) return;

            const btn = this;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Removendo...';
            btn.disabled = true;

            fetch(window.DOMAIN + '/agendamento/remover', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + window.agendamentoParaRemover
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                        if (dataTableInstance) {
                            dataTableInstance.reload();
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro!', text: data.message });
                }
            })
            .catch(function(error) {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao remover o agendamento.'
                });
            })
            .finally(function() {
                btn.innerHTML = 'Sim, Remover';
                btn.disabled = false;
                window.agendamentoParaRemover = null;
            });
        });
    }
})();
