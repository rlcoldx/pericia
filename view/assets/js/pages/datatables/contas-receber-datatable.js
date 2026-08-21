(function() {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    let dataTableInstance = null;

    function renderHtml(data) {
        return data == null ? '-' : data;
    }

    function init() {
        const table = document.getElementById('datatable-contas-receber');
        if (!table || typeof DataTableAjax === 'undefined') return;

        // Colunas nomeadas — devem bater com ContaReceberController::datatable
        const columns = [
            { data: 'reclamante', name: 'reclamante', orderable: true, searchable: true, defaultContent: '-', render: renderHtml },
            { data: 'valor', name: 'valor', orderable: true, searchable: false, defaultContent: '-', render: renderHtml },
            { data: 'situacao', name: 'situacao', orderable: true, searchable: true, defaultContent: '-', render: renderHtml },
            { data: 'numero_pedido', name: 'numero_pedido', orderable: true, searchable: true, defaultContent: '-', render: renderHtml },
            { data: 'numero_nota_fiscal', name: 'numero_nota_fiscal', orderable: true, searchable: true, defaultContent: '-', render: renderHtml },
            { data: 'numero_boleto', name: 'numero_boleto', orderable: true, searchable: true, defaultContent: '-', render: renderHtml },
            { data: 'data_envio', name: 'data_envio', orderable: true, searchable: false, defaultContent: '-', render: renderHtml },
            { data: 'prazo', name: 'prazo', orderable: true, searchable: false, defaultContent: '-', render: renderHtml },
            { data: 'status', name: 'status', orderable: true, searchable: true, defaultContent: '-', render: renderHtml },
            { data: 'acoes', name: 'acoes', orderable: false, searchable: false, defaultContent: '', render: renderHtml, className: 'text-nowrap' }
        ];

        const config = {
            ajaxUrl: window.DOMAIN + '/contas-receber/datatable',
            columns: columns,
            order: [[7, 'asc']], // Prazo
            pageLength: 25,
            customFilters: getFiltersFromForm(),
            onDraw: function() {
                if (typeof bootstrap !== 'undefined') {
                    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                        try {
                            const existing = bootstrap.Tooltip.getInstance(el);
                            if (existing) existing.dispose();
                            new bootstrap.Tooltip(el);
                        } catch (e) {}
                    });
                }
            }
        };

        dataTableInstance = new DataTableAjax('datatable-contas-receber', config);
        dataTableInstance.init();

        setupFilterForm();
        setupActionButtons();
    }

    function getFiltersFromForm() {
        const form = document.getElementById('filtrosContasReceber');
        if (!form) return {};

        const formData = new FormData(form);
        const filters = {};

        if (formData.get('status')) filters.status = formData.get('status');
        if (formData.get('situacao')) filters.situacao = formData.get('situacao');
        if (formData.get('data_inicio')) filters.data_inicio = formData.get('data_inicio');
        if (formData.get('data_fim')) filters.data_fim = formData.get('data_fim');
        if (formData.get('numero_processo')) filters.numero_processo = formData.get('numero_processo');

        return filters;
    }

    function setupFilterForm() {
        const form = document.getElementById('filtrosContasReceber');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (dataTableInstance) {
                dataTableInstance.updateFilters(getFiltersFromForm());
            }
        });

        const btnLimpar = document.getElementById('btnLimparFiltros');
        if (btnLimpar) {
            btnLimpar.addEventListener('click', function() {
                form.reset();
                if (dataTableInstance) {
                    dataTableInstance.updateFilters({});
                }
            });
        }
    }

    function setupActionButtons() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remover-conta')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-remover-conta');
                const contaId = btn.dataset.id;
                const contaDescricao = btn.dataset.descricao || 'esta conta';

                Swal.fire({
                    title: 'Confirmar Remoção',
                    text: 'Tem certeza que deseja remover ' + contaDescricao + '?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, remover',
                    cancelButtonText: 'Cancelar'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        removerConta(contaId);
                    }
                });
            }
        });
    }

    function removerConta(id) {
        fetch(window.location.origin + '/contas-receber/remover', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'id=' + encodeURIComponent(id)
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
                    if (dataTableInstance) {
                        dataTableInstance.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: data.message
                });
            }
        })
        .catch(function() {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Não foi possível remover a conta a receber.'
            });
        });
    }
})();
