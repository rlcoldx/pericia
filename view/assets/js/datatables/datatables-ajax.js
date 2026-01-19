/**
 * DataTables AJAX - Configuração Base
 * Sistema reutilizável para DataTables com Server-Side Processing
 */

(function() {
    'use strict';

    /**
     * Classe base para DataTables AJAX
     */
    class DataTableAjax {
        constructor(tableId, config) {
            this.tableId = tableId;
            this.config = {
                ajax: {
                    url: config.ajaxUrl || '',
                    type: 'GET',
                    data: function(d) {
                        // Adiciona filtros customizados
                        if (config.customFilters) {
                            Object.keys(config.customFilters).forEach(key => {
                                d[key] = config.customFilters[key];
                            });
                        }
                        return d;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('Erro ao carregar dados:', error);
                        if (config.onError) {
                            config.onError(xhr, error, thrown);
                        }
                    }
                },
                processing: true,
                serverSide: true,
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
                    processing: '<i class="fa fa-spinner fa-spin fa-2x"></i> Carregando dados...'
                },
                columns: config.columns || [],
                order: config.order || [[1, 'desc']],
                pageLength: config.pageLength || 10,
                lengthMenu: config.lengthMenu || [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                dom: config.dom || 'lfrtip',
                drawCallback: function(settings) {
                    // Reinicializa tooltips do Bootstrap após cada draw
                    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip !== 'undefined') {
                        // Remove tooltips existentes primeiro para evitar conflitos
                        const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                        existingTooltips.forEach(el => {
                            const existingTooltip = bootstrap.Tooltip.getInstance(el);
                            if (existingTooltip) {
                                existingTooltip.dispose();
                            }
                        });
                        
                        // Cria novos tooltips
                        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                        tooltipTriggerList.forEach(tooltipTriggerEl => {
                            if (tooltipTriggerEl && tooltipTriggerEl.nodeType === 1) {
                                try {
                                    new bootstrap.Tooltip(tooltipTriggerEl);
                                } catch (e) {
                                    console.warn('Erro ao criar tooltip:', e);
                                }
                            }
                        });
                    }
                    
                    // Callback customizado
                    if (config.onDraw) {
                        config.onDraw(settings);
                    }
                },
                initComplete: function(settings, json) {
                    if (config.onInit) {
                        config.onInit(settings, json);
                    }
                }
            };

            // Mescla configurações customizadas
            if (config.customConfig) {
                // Se tem ajax.data customizado, mesclar de forma especial
                if (config.customConfig.ajax && config.customConfig.ajax.data) {
                    const originalDataFn = this.config.ajax.data;
                    const customDataFn = config.customConfig.ajax.data;
                    
                    // Mesclar outras propriedades do ajax primeiro
                    Object.keys(config.customConfig.ajax).forEach(key => {
                        if (key !== 'data') {
                            this.config.ajax[key] = config.customConfig.ajax[key];
                        }
                    });
                    
                    // Criar função data que combina ambas
                    this.config.ajax.data = function(d) {
                        // Primeiro executa a função original (para customFilters)
                        if (originalDataFn) {
                            originalDataFn(d);
                        }
                        // Depois executa a função customizada (para filtros dinâmicos)
                        if (customDataFn) {
                            customDataFn(d);
                        }
                        return d;
                    };
                }
                
                // Mesclar outras configurações (não ajax)
                Object.keys(config.customConfig).forEach(key => {
                    if (key !== 'ajax') {
                        this.config[key] = config.customConfig[key];
                    }
                });
            }

            this.table = null;
        }

        /**
         * Inicializa o DataTable
         */
        init() {
            if (!this.tableId || !document.getElementById(this.tableId)) {
                console.error('DataTable: Elemento não encontrado:', this.tableId);
                return null;
            }

            this.table = $(`#${this.tableId}`).DataTable(this.config);
            return this.table;
        }

        /**
         * Recarrega os dados da tabela
         */
        reload() {
            if (this.table) {
                this.table.ajax.reload();
            }
        }

        /**
         * Atualiza filtros customizados
         */
        updateFilters(filters) {
            if (this.config.ajax && this.config.ajax.data) {
                const originalData = this.config.ajax.data;
                this.config.ajax.data = function(d) {
                    originalData(d);
                    Object.keys(filters).forEach(key => {
                        d[key] = filters[key];
                    });
                    return d;
                };
                this.reload();
            }
        }

        /**
         * Destrói a instância do DataTable
         */
        destroy() {
            if (this.table) {
                this.table.destroy();
                this.table = null;
            }
        }
    }

    // Expõe globalmente
    window.DataTableAjax = DataTableAjax;

})();

