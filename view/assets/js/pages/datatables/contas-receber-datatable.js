(function() {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    let dataTableInstance = null;

    function init() {
        const table = document.getElementById('datatable-contas-receber');
        if (!table || typeof DataTableAjax === 'undefined') return;

        const columns = [
            { data: 0, name: 'local', orderable: true, searchable: true },
            { data: 1, name: 'reclamante', orderable: true, searchable: true },
            { data: 2, name: 'tipo', orderable: true, searchable: true },
            { data: 3, name: 'etapa', orderable: true, searchable: false },
            { data: 4, name: 'valor', orderable: true, searchable: false },
            { data: 5, name: 'processo', orderable: true, searchable: true },
            { data: 6, name: 'data_pericia', orderable: true, searchable: false },
            { data: 7, name: 'situacao', orderable: true, searchable: true },
            { data: 8, name: 'numero_pedido', orderable: true, searchable: true },
            { data: 9, name: 'numero_nota_fiscal', orderable: true, searchable: true },
            { data: 10, name: 'numero_boleto', orderable: true, searchable: true },
            { data: 11, name: 'data_envio', orderable: true, searchable: false },
            { data: 12, name: 'prazo', orderable: true, searchable: false },
            { data: 13, name: 'status', orderable: true, searchable: true },
            { data: 14, name: 'assistente', orderable: true, searchable: true },
            { data: 15, name: 'valor_assistente', orderable: true, searchable: false },
            { data: 16, name: 'acoes', orderable: false, searchable: false }
        ];

        const config = {
            ajaxUrl: window.DOMAIN + '/contas-receber/datatable',
            columns: columns,
            order: [[12, 'asc']], // Ordena por prazo
            pageLength: 25,
            customFilters: getFiltersFromForm(),
            onDraw: function(settings) {
                if (typeof bootstrap !== 'undefined') {
                    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
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
        // Delegação de eventos para botões de ação
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remover-conta')) {
                e.preventDefault();
                const contaId = e.target.closest('.btn-remover-conta').dataset.id;
                const contaDescricao = e.target.closest('.btn-remover-conta').dataset.descricao || 'esta conta';
                
                Swal.fire({
                    title: 'Confirmar Remoção',
                    text: `Tem certeza que deseja remover ${contaDescricao}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, remover',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        removerConta(contaId);
                    }
                });
            }
        });
    }

    function removerConta(id) {
        fetch(window.DOMAIN + '/contas-receber/remover', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
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
        });
    }
})();

