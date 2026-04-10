/**
 * DataTable específico para Manifestações e Impugnações
 * Configuração e inicialização do DataTable com AJAX
 */

(function() {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    let dataTableInstance = null;

    function init() {
        const table = document.getElementById('datatable-manifestacoes');
        if (!table) return;

        if (typeof DataTableAjax === 'undefined') {
            console.error('DataTableAjax não está carregado. Certifique-se de incluir datatables-ajax.js antes deste arquivo.');
            return;
        }

        const columns = [
            { data: 0, name: 'data', orderable: true, searchable: false },
            { data: 1, name: 'tipo', orderable: true, searchable: true },
            { data: 2, name: 'tipo_trabalho', orderable: true, searchable: true },
            { data: 3, name: 'numero', orderable: true, searchable: true },
            { data: 4, name: 'reclamada', orderable: true, searchable: true },
            { data: 5, name: 'reclamante', orderable: true, searchable: true },
            { data: 6, name: 'favoravel', orderable: true, searchable: false },
            { data: 7, name: 'status', orderable: true, searchable: false },
            { data: 8, name: 'perito', orderable: true, searchable: true },
            { data: 9, name: 'acoes', orderable: false, searchable: false, className: 'text-center text-nowrap' }
        ];

        const config = {
            ajaxUrl: window.DOMAIN + '/manifestacoes-impugnacoes/datatable',
            columns: columns,
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            customFilters: {},
            /** Evita colunas “responsive” que somam células e desalinham cabeçalho/dados */
            customConfig: {
                responsive: false,
                autoWidth: false
            },
            onDraw: function() {},
            onError: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao carregar os dados da tabela.'
                });
            }
        };

        dataTableInstance = new DataTableAjax('datatable-manifestacoes', config);
        dataTableInstance.init();

        bindExcluirManifestacao();
    }

    function bindExcluirManifestacao() {
        if (bindExcluirManifestacao._done) {
            return;
        }
        bindExcluirManifestacao._done = true;

        document.body.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-excluir-manifestacao');
            if (!btn) {
                return;
            }
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            if (!id || typeof Swal === 'undefined') {
                return;
            }

            Swal.fire({
                title: 'Excluir manifestação?',
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
                fetch(window.DOMAIN + '/manifestacoes-impugnacoes/excluir', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            Swal.fire('Excluída', data.message || '', 'success');
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

})();
