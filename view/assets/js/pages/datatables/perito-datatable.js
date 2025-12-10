/**
 * DataTable específico para Peritos
 * Configuração e inicialização do DataTable de peritos com AJAX
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
        const table = document.getElementById('datatable-peritos');
        if (!table) return;

        // Verifica se o DataTableAjax está disponível
        if (typeof DataTableAjax === 'undefined') {
            console.error('DataTableAjax não está carregado. Certifique-se de incluir datatables-ajax.js antes deste arquivo.');
            return;
        }

        // Configuração das colunas
        const columns = [
            {
                data: 0, // Nome (HTML formatado)
                name: 'nome',
                orderable: true,
                searchable: true
            },
            {
                data: 1, // Email (HTML formatado)
                name: 'email',
                orderable: true,
                searchable: true
            },
            {
                data: 2, // Telefone (HTML formatado)
                name: 'telefone',
                orderable: true,
                searchable: true
            },
            {
                data: 3, // Especialidade (HTML formatado)
                name: 'especialidade',
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
                data: 5, // Ações (HTML formatado)
                name: 'acoes',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ];

        // Configuração do DataTable
        const config = {
            ajaxUrl: window.DOMAIN + '/perito/datatable',
            columns: columns,
            order: [[0, 'asc']], // Ordena por nome por padrão
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
        dataTableInstance = new DataTableAjax('datatable-peritos', config);
        dataTableInstance.init();

        // Configura formulário de filtros
        setupFilterForm();
        
        // Configura função de remoção
        setupRemoveFunction();
    }

    /**
     * Obtém filtros do formulário
     */
    function getFiltersFromForm() {
        const form = document.getElementById('filtrosPerito');
        if (!form) return {};

        const formData = new FormData(form);
        const filters = {};

        // Status
        const status = formData.get('status');
        if (status) filters.status = status;

        // Especialidade
        const especialidade = formData.get('especialidade');
        if (especialidade) filters.especialidade = especialidade;

        return filters;
    }

    /**
     * Configura o formulário de filtros
     */
    function setupFilterForm() {
        filterForm = document.getElementById('filtrosPerito');
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
     * Configura função de remoção
     */
    function setupRemoveFunction() {
        /**
         * Função global para remover perito (chamada pelos botões de ação)
         */
        window.removerPerito = function(id, nomePerito) {
            if (typeof window.peritoParaRemover === 'undefined') {
                window.peritoParaRemover = null;
            }
            
            window.peritoParaRemover = id;
            const nomeElement = document.getElementById('nomePerito');
            if (nomeElement) {
                nomeElement.textContent = nomePerito;
            }
            
            const modalElement = document.getElementById('confirmModal');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        };

        /**
         * Confirma remoção do perito
         */
        const confirmRemover = document.getElementById('confirmRemover');
        if (confirmRemover) {
            confirmRemover.addEventListener('click', function() {
                if (!window.peritoParaRemover) return;
                
                const btn = this;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Removendo...';
                btn.disabled = true;
                
                fetch(window.DOMAIN + '/perito/remover', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + window.peritoParaRemover
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
                            const modalElement = document.getElementById('confirmModal');
                            if (modalElement) {
                                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                            }
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
                        text: 'Ocorreu um erro ao remover o perito.'
                    });
                })
                .finally(() => {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    window.peritoParaRemover = null;
                });
            });
        }
    }

})();

