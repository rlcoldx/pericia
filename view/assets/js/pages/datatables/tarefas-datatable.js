/**
 * DataTable específico para Tarefas do Usuário
 * Configuração e inicialização do DataTable de tarefas com AJAX
 */

(function() {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    let dataTableInstance = null;
    let currentFilters = {};

    function init() {
        const table = document.getElementById('tabelaTarefas');
        if (!table) return;

        if (typeof DataTableAjax === 'undefined') {
            console.error('DataTableAjax não está carregado. Certifique-se de incluir datatables-ajax.js antes deste arquivo.');
            return;
        }

        const columns = [
            { data: 0, name: 'modulo', orderable: true, searchable: true },
            { data: 1, name: 'reclamada', orderable: true, searchable: true },
            { data: 2, name: 'status', orderable: true, searchable: false },
            { data: 3, name: 'data_conclusao', orderable: true, searchable: false },
            { data: 4, name: 'data_create', orderable: true, searchable: false },
            { data: 5, name: 'acoes', orderable: false, searchable: false, className: 'text-center' }
        ];

        // Função para obter filtros do formulário dinamicamente
        function getFiltersFromForm() {
            const filtroStatus = document.getElementById('filtroStatusTarefas');
            const filters = {};
            
            if (filtroStatus && filtroStatus.value) {
                filters.status = filtroStatus.value;
            }
            
            return filters;
        }

        const config = {
            ajaxUrl: window.DOMAIN + '/home/tarefas/datatable',
            columns: columns,
            order: [[4, 'desc']], // Ordenar por data de criação (mais recente primeiro)
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            customFilters: getFiltersFromForm(),
            customConfig: {
                ajax: {
                    data: function(d) {
                        // Adiciona filtro de status dinamicamente do select
                        const filtroStatus = document.getElementById('filtroStatusTarefas');
                        if (filtroStatus && filtroStatus.value) {
                            d.status = filtroStatus.value;
                        } else {
                            d.status = '';
                        }
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
            onError: function(xhr, error, thrown) {
                console.error('Erro ao carregar tarefas:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao carregar as tarefas.'
                });
            }
        };

        dataTableInstance = new DataTableAjax('tabelaTarefas', config);
        if (dataTableInstance.init()) {
            console.log('DataTable de tarefas inicializado com sucesso');
            
            // Configurar filtro de status
            setupStatusFilter();
        } else {
            console.error('Erro ao inicializar DataTable de tarefas');
        }
    }

    /**
     * Configura o filtro de status das tarefas
     */
    function setupStatusFilter() {
        const filtroStatus = document.getElementById('filtroStatusTarefas');
        if (!filtroStatus) return;

        filtroStatus.addEventListener('change', function() {
            if (dataTableInstance && dataTableInstance.reload) {
                // Recarrega a tabela com os novos filtros
                dataTableInstance.reload();
            }
        });
    }

    // Função para recarregar a tabela (pode ser chamada externamente)
    window.reloadTarefasTable = function() {
        if (dataTableInstance && dataTableInstance.reload) {
            dataTableInstance.reload();
        }
    };
})();
