/**
 * DataTable específico para Agendamentos
 * Configuração e inicialização do DataTable de agendamentos com AJAX
 */

(function() {
    'use strict';

    // Aguarda o DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    let dataTableInstance = null;
    let filterForm = null;

    function init() {
        const table = document.getElementById('datatable-agendamentos');
        if (!table) return;

        // Verifica se o DataTableAjax está disponível
        if (typeof DataTableAjax === 'undefined') {
            console.error('DataTableAjax não está carregado. Certifique-se de incluir datatables-ajax.js antes deste arquivo.');
            return;
        }

        // Configuração das colunas
        // O DataTables espera que os dados venham como array, então usamos índices
        const columns = [
            {
                data: 0, // Cliente (HTML formatado)
                name: 'cliente_nome',
                orderable: true,
                searchable: true
            },
            {
                data: 1, // Data/Hora (HTML formatado)
                name: 'data_agendamento',
                orderable: true,
                searchable: false
            },
            {
                data: 2, // Perito (HTML formatado)
                name: 'perito_id',
                orderable: true,
                searchable: false
            },
            {
                data: 3, // Tipo (HTML formatado)
                name: 'tipo_pericia',
                orderable: true,
                searchable: true
            },
            {
                data: 4, // Status (HTML formatado)
                name: 'status',
                orderable: true,
                searchable: false
            },
            {
                data: 5, // Local (HTML formatado)
                name: 'local_pericia',
                orderable: false,
                searchable: true
            },
            {
                data: 6, // Ações (HTML formatado)
                name: 'acoes',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ];

        // Configuração do DataTable
        const config = {
            ajaxUrl: window.DOMAIN + '/agendamento/datatable',
            columns: columns,
            order: [[1, 'desc']], // Ordena por data por padrão
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            customFilters: getFiltersFromForm(),
            onDraw: function(settings) {
                // Callback após desenhar a tabela
                console.log('Tabela atualizada');
            },
            onError: function(xhr, error, thrown) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao carregar os dados da tabela.'
                });
            }
        };

        // Inicializa o DataTable
        dataTableInstance = new DataTableAjax('datatable-agendamentos', config);
        dataTableInstance.init();

        // Configura formulário de filtros
        setupFilterForm();
    }

    /**
     * Obtém filtros do formulário
     */
    function getFiltersFromForm() {
        const form = document.getElementById('filtrosAgendamento');
        if (!form) return {};

        const formData = new FormData(form);
        const filters = {};

        // Status
        const status = formData.get('status');
        if (status) filters.status = status;

        // Perito
        const peritoId = formData.get('perito_id');
        if (peritoId) filters.perito_id = peritoId;

        // Data início
        const dataInicio = formData.get('data_inicio');
        if (dataInicio) filters.data_inicio = dataInicio;

        // Data fim
        const dataFim = formData.get('data_fim');
        if (dataFim) filters.data_fim = dataFim;

        return filters;
    }

    /**
     * Configura o formulário de filtros
     */
    function setupFilterForm() {
        filterForm = document.getElementById('filtrosAgendamento');
        if (!filterForm) return;

        // Intercepta submit do formulário
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Atualiza filtros e recarrega a tabela
            if (dataTableInstance) {
                dataTableInstance.updateFilters(getFiltersFromForm());
            }
        });

        // Adiciona listener para limpar filtros
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

    /**
     * Função global para remover agendamento (chamada pelos botões de ação)
     */
    window.removerAgendamento = function(id, nomeCliente) {
        if (typeof agendamentoParaRemover === 'undefined') {
            window.agendamentoParaRemover = null;
        }
        
        window.agendamentoParaRemover = id;
        const nomeElement = document.getElementById('nomeCliente');
        if (nomeElement) {
            nomeElement.textContent = nomeCliente;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
    };

    /**
     * Confirma remoção do agendamento
     */
    const confirmRemover = document.getElementById('confirmRemover');
    if (confirmRemover) {
        confirmRemover.addEventListener('click', function() {
            if (!window.agendamentoParaRemover) return;
            
            const btn = this;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Removendo...';
            btn.disabled = true;
            
            fetch(window.DOMAIN + '/agendamento/remover', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + window.agendamentoParaRemover
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
                        bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                        // Recarrega a tabela
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
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao remover o agendamento.'
                });
            })
            .finally(() => {
                btn.innerHTML = 'Sim, Remover';
                btn.disabled = false;
                window.agendamentoParaRemover = null;
            });
        });
    }

})();

