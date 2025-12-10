/**
 * DataTable específico para Reclamadas
 * Configuração e inicialização do DataTable de reclamadas com AJAX
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
        const table = document.getElementById('datatable-reclamadas');
        if (!table) return;

        if (typeof DataTableAjax === 'undefined') {
            console.error('DataTableAjax não está carregado.');
            return;
        }

        const columns = [
            { data: 0, name: 'nome', orderable: true, searchable: true },
            { data: 1, name: 'nome_contato', orderable: true, searchable: true },
            { data: 2, name: 'email_contato', orderable: true, searchable: true },
            { data: 3, name: 'telefone_contato', orderable: true, searchable: true },
            { data: 4, name: 'acoes', orderable: false, searchable: false, className: 'text-center' }
        ];

        const config = {
            ajaxUrl: window.DOMAIN + '/reclamadas/datatable',
            columns: columns,
            order: [[0, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            onError: function(xhr, error, thrown) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao carregar os dados da tabela.'
                });
            }
        };

        dataTableInstance = new DataTableAjax('datatable-reclamadas', config);
        dataTableInstance.init();

        setupRemoveFunction();
    }

    function setupRemoveFunction() {
        window.removerReclamada = function(id, nome) {
            document.getElementById('nomeReclamada').textContent = nome;
            window.reclamadaParaRemover = id;
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        };

        const confirmRemover = document.getElementById('confirmRemover');
        if (confirmRemover) {
            confirmRemover.addEventListener('click', function() {
                if (!window.reclamadaParaRemover) return;
                
                const btn = this;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Removendo...';
                btn.disabled = true;
                
                fetch(window.DOMAIN + '/reclamadas/remover', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + window.reclamadaParaRemover
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
                            if (dataTableInstance) {
                                dataTableInstance.reload();
                            }
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro!', text: data.message });
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    Swal.fire({ icon: 'error', title: 'Erro!', text: 'Ocorreu um erro ao remover.' });
                })
                .finally(() => {
                    btn.innerHTML = 'Sim, Remover';
                    btn.disabled = false;
                    window.reclamadaParaRemover = null;
                });
            });
        }
    }
})();
