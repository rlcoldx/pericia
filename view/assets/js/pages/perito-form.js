/**
 * Script para formulário de perito
 * Gerencia máscaras de CPF/CNPJ, telefone e submissão do formulário
 */

(function() {
    'use strict';

    // Aguarda o DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        const form = document.getElementById('formPerito');
        if (!form) return;

        // Inicializa máscaras
        initMasks();
        
        // Inicializa submissão do formulário
        initFormSubmit(form);
    }

    /**
     * Inicializa máscaras de CPF/CNPJ e telefone
     */
    function initMasks() {
        const tipoDocumentoSelect = document.getElementById('tipo_documento');
        const documentoInput = document.getElementById('documento');
        const telefoneInput = document.querySelector('input[name="telefone"]');

        // Máscara de CPF/CNPJ baseada no tipo selecionado
        if (tipoDocumentoSelect && documentoInput) {
            // Detecta automaticamente o tipo baseado no valor inicial
            if (documentoInput.value) {
                const valueLimpo = documentoInput.value.replace(/\D/g, '');
                if (valueLimpo.length === 14) {
                    tipoDocumentoSelect.value = 'CNPJ';
                    documentoInput.value = maskCNPJ(valueLimpo);
                } else if (valueLimpo.length === 11) {
                    tipoDocumentoSelect.value = 'CPF';
                    documentoInput.value = maskCPF(valueLimpo);
                } else {
                    // Tenta detectar pelo formato atual
                    if (documentoInput.value.includes('/')) {
                        tipoDocumentoSelect.value = 'CNPJ';
                    } else {
                        tipoDocumentoSelect.value = 'CPF';
                    }
                }
            }

            tipoDocumentoSelect.addEventListener('change', function() {
                const valueLimpo = documentoInput.value.replace(/\D/g, '');
                documentoInput.value = '';
                documentoInput.placeholder = this.value === 'CNPJ' ? '00.000.000/0000-00' : '000.000.000-00';
                
                // Se havia valor, reaplica a máscara correta
                if (valueLimpo) {
                    if (this.value === 'CNPJ' && valueLimpo.length <= 14) {
                        documentoInput.value = maskCNPJ(valueLimpo);
                    } else if (this.value === 'CPF' && valueLimpo.length <= 11) {
                        documentoInput.value = maskCPF(valueLimpo);
                    }
                }
            });

            documentoInput.addEventListener('input', function(e) {
                const tipo = tipoDocumentoSelect.value || 'CPF';
                const value = e.target.value.replace(/\D/g, '');
                
                if (tipo === 'CNPJ') {
                    e.target.value = maskCNPJ(value);
                } else {
                    e.target.value = maskCPF(value);
                }
            });

            // Atualiza placeholder inicial
            documentoInput.placeholder = tipoDocumentoSelect.value === 'CNPJ' ? '00.000.000/0000-00' : '000.000.000-00';
        }

        // Máscara de telefone
        if (telefoneInput) {
            telefoneInput.addEventListener('input', function(e) {
                const value = e.target.value.replace(/\D/g, '');
                e.target.value = maskTelefone(value);
            });

            // Aplica máscara inicial se já houver valor
            if (telefoneInput.value) {
                const value = telefoneInput.value.replace(/\D/g, '');
                telefoneInput.value = maskTelefone(value);
            }
        }
    }

    /**
     * Aplica máscara de CPF
     */
    function maskCPF(value) {
        value = value.substring(0, 11);
        
        if (value.length === 0) return '';
        if (value.length <= 3) return value;
        if (value.length <= 6) return value.replace(/(\d{3})(\d+)/, '$1.$2');
        if (value.length <= 9) return value.replace(/(\d{3})(\d{3})(\d+)/, '$1.$2.$3');
        return value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }

    /**
     * Aplica máscara de CNPJ
     */
    function maskCNPJ(value) {
        value = value.substring(0, 14);
        
        if (value.length === 0) return '';
        if (value.length <= 2) return value;
        if (value.length <= 5) return value.replace(/(\d{2})(\d+)/, '$1.$2');
        if (value.length <= 8) return value.replace(/(\d{2})(\d{3})(\d+)/, '$1.$2.$3');
        if (value.length <= 12) return value.replace(/(\d{2})(\d{3})(\d{3})(\d+)/, '$1.$2.$3/$4');
        return value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    }

    /**
     * Aplica máscara de telefone
     */
    function maskTelefone(value) {
        if (value.length <= 10) {
            if (value.length <= 2) {
                return value.length > 0 ? '(' + value : value;
            } else if (value.length <= 6) {
                return value.replace(/(\d{2})(\d+)/, '($1) $2');
            } else {
                return value.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
            }
        } else {
            return value.substring(0, 11).replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
    }

    /**
     * Inicializa submissão do formulário
     */
    function initFormSubmit(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Mostra loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Salvando...';
            submitBtn.disabled = true;
            
            // Prepara dados do formulário
            const formData = new FormData(this);
            
            // Processa documento: envia o valor formatado
            const documentoInput = document.getElementById('documento');
            if (documentoInput && documentoInput.value) {
                formData.set('documento', documentoInput.value);
            }
            
            // Faz a requisição
            const action = form.querySelector('input[name="id"]') ? 'edit' : 'criar';
            const url = window.DOMAIN + '/perito/' + (action === 'criar' ? 'add/save' : 'edit/save');
            
            fetch(url, {
                method: 'POST',
                body: formData
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
                        window.location.href = window.DOMAIN + '/perito';
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
                    text: 'Ocorreu um erro ao salvar o perito.'
                });
            })
            .finally(() => {
                // Restaura o botão
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }

})();

