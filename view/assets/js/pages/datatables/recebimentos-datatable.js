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
        const table = document.getElementById('datatable-recebimentos');
        if (!table || typeof DataTableAjax === 'undefined') return;

        const columns = [
            { data: 'descricao', name: 'descricao', orderable: true, searchable: true, defaultContent: '-', render: renderHtml },
            { data: 'valor', name: 'valor', orderable: true, searchable: false, defaultContent: '-', render: renderHtml },
            { data: 'data_recebimento', name: 'data_recebimento', orderable: true, searchable: false, defaultContent: '-', render: renderHtml },
            { data: 'forma_pagamento', name: 'forma_pagamento', orderable: true, searchable: true, defaultContent: '-', render: renderHtml },
            { data: 'status', name: 'status', orderable: true, searchable: true, defaultContent: '-', render: renderHtml },
            { data: 'conta_descricao', name: 'conta_descricao', orderable: false, searchable: true, defaultContent: '-', render: renderHtml },
            { data: 'acoes', name: 'acoes', orderable: false, searchable: false, defaultContent: '', render: renderHtml, className: 'text-nowrap' }
        ];

        const config = {
            ajaxUrl: (window.DOMAIN || '').replace(/\/$/, '') + '/recebimentos/datatable',
            columns: columns,
            order: [[2, 'desc']],
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

        dataTableInstance = new DataTableAjax('datatable-recebimentos', config);
        dataTableInstance.init();

        setupFilterForm();
        setupActionButtons();
    }

    function getFiltersFromForm() {
        const form = document.getElementById('filtrosRecebimentos');
        if (!form) return {};

        const formData = new FormData(form);
        const filters = {};
        if (formData.get('status')) filters.status = formData.get('status');
        if (formData.get('data_inicio')) filters.data_inicio = formData.get('data_inicio');
        if (formData.get('data_fim')) filters.data_fim = formData.get('data_fim');
        return filters;
    }

    function setupFilterForm() {
        const form = document.getElementById('filtrosRecebimentos');
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
            const btn = e.target.closest('.btn-remover-recebimento');
            if (!btn) return;
            e.preventDefault();

            const id = btn.dataset.id;
            Swal.fire({
                title: 'Confirmar Remoção',
                text: 'Tem certeza que deseja remover este recebimento?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    removerRecebimento(id);
                }
            });
        });
    }

    function removerRecebimento(id) {
        const base = (window.DOMAIN || window.location.origin).replace(/\/$/, '');
        fetch(base + '/recebimentos/remover', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: 'id=' + encodeURIComponent(id)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Sucesso!', text: data.message, timer: 2000, showConfirmButton: false })
                    .then(function() {
                        if (dataTableInstance) dataTableInstance.reload();
                        window.location.reload();
                    });
            } else {
                Swal.fire({ icon: 'error', title: 'Erro!', text: data.message || 'Não foi possível remover.' });
            }
        })
        .catch(function() {
            Swal.fire({ icon: 'error', title: 'Erro!', text: 'Falha ao remover recebimento.' });
        });
    }
})();
