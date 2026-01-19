/**
 * Script para formulário de agendamento
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
        const form = document.getElementById('formAgendamento');
        if (!form) return;

        // Máscaras de moeda são aplicadas globalmente no custom.min.js
        // Apenas garante que campos dinâmicos também recebam a máscara
        setTimeout(function() {
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.mask !== 'undefined') {
                jQuery('.money').each(function() {
                    if (!jQuery(this).data('mask-applied')) {
                        jQuery(this).mask('#.##0,00', {reverse: true});
                        jQuery(this).data('mask-applied', true);
                    }
                });
            }
        }, 200);
        
        // Inicializa validação de data
        initDateValidation();
        
        // Inicializa Select2 para campos com busca
        initSelect2();
        
        // Inicializa submissão do formulário
        initFormSubmit(form);
    }

    /**
     * Inicializa Select2 para campos com busca (Assistente, Perito, etc)
     */
    function initSelect2() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            return;
        }

        // Select2 para Assistente (agendamento) e Assistente do Parecer
        ['#assistente_id', '#parecer_assistente_id'].forEach(function(selector) {
            const select = jQuery(selector);
            if (select.length) {
                select.select2({
                    placeholder: 'Selecione o Assistente',
                    allowClear: true,
                    width: '100%',
                    language: {
                        noResults: function() {
                            return "Nenhum assistente encontrado";
                        },
                        searching: function() {
                            return "Buscando...";
                        }
                    }
                });
            }
        });

        // Select2 para Reclamada do Parecer com tags (permite criar novos)
        const reclamadaParecerSelect = jQuery('#parecer_reclamada_id');
        if (reclamadaParecerSelect.length) {
            reclamadaParecerSelect.select2({
                tags: true,
                placeholder: 'Selecione ou digite para criar um novo',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Digite para criar um novo";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        }

        // Select2 para Reclamante do Parecer com tags (permite criar novos)
        const reclamanteParecerSelect = jQuery('#parecer_reclamante_id');
        if (reclamanteParecerSelect.length) {
            reclamanteParecerSelect.select2({
                tags: true,
                placeholder: 'Selecione ou digite para criar um novo',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Digite para criar um novo";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        }

        // Select2 com tags para Tipo de Parecer (força maiúsculas)
        const tipoParecerSelect = jQuery('#parecer_tipo');
        if (tipoParecerSelect.length) {
            tipoParecerSelect.select2({
                tags: true,
                placeholder: 'Selecione ou digite um tipo',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Digite para criar um novo";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                },
                createTag: function (params) {
                    const term = jQuery.trim(params.term);
                    if (term === '') {
                        return null;
                    }
                    return {
                        id: term.toUpperCase(),
                        text: term.toUpperCase(),
                        newTag: true
                    };
                }
            });

            // Forçar maiúsculas ao digitar
            tipoParecerSelect.on('select2:open', function() {
                setTimeout(function() {
                    jQuery('.select2-search__field').on('input', function() {
                        const val = jQuery(this).val();
                        if (val && val !== val.toUpperCase()) {
                            jQuery(this).val(val.toUpperCase());
                        }
                    });
                }, 0);
            });
        }

        // Select2 para Usuário Responsável (Tarefas)
        const usuarioResponsavelSelect = jQuery('#tarefa_usuario_responsavel_id');
        if (usuarioResponsavelSelect.length) {
            usuarioResponsavelSelect.select2({
                placeholder: 'Selecione o usuário responsável',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Nenhum usuário encontrado";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        }

        // Select2 para Reclamante Nome com tags (permite criar novos)
        const reclamanteNomeSelect = jQuery('#reclamante_nome');
        if (reclamanteNomeSelect.length) {
            reclamanteNomeSelect.select2({
                tags: true,
                placeholder: 'Selecione ou digite para criar um novo Reclamante',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Digite para criar um novo reclamante";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        }

        // Select2 para Cliente Nome (Reclamada) com tags (permite criar novos)
        const clienteNomeSelect = jQuery('#cliente_nome');
        if (clienteNomeSelect.length) {
            clienteNomeSelect.select2({
                tags: true,
                placeholder: 'Selecione ou digite para criar uma nova Reclamada',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Digite para criar uma nova reclamada";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        }
    }



    /**
     * Inicializa validação de data (não permitir datas passadas)
     */
    function initDateValidation() {
        const dataInput = document.querySelector('input[name="data_agendamento"]');
        if (dataInput) {
            const hoje = new Date().toISOString().split('T')[0];
            dataInput.setAttribute('min', hoje);
        }
    }

    /**
     * Cria um reclamante/reclamada rapidamente via AJAX
     */
    async function criarRapido(tipo, nome) {
        const endpoint = tipo === 'reclamante' 
            ? window.DOMAIN + '/reclamantes/criar-rapido'
            : window.DOMAIN + '/reclamadas/criar-rapido';
        
        const formData = new FormData();
        formData.append('nome', nome);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                return { success: true, id: data.id, nome: data.nome };
            } else {
                throw new Error(data.message || 'Erro ao criar ' + tipo);
            }
        } catch (error) {
            console.error('Erro ao criar ' + tipo + ':', error);
            throw error;
        }
    }

    /**
     * Verifica se um valor existe nas opções do select
     */
    function valorExisteNoSelect(selectId, valor) {
        if (!valor || valor === '') {
            return false;
        }
        
        const select = document.getElementById(selectId);
        if (!select) return false;
        
        // Se o valor é um número, verificar se existe nas opções
        const valorInt = parseInt(valor);
        if (!isNaN(valorInt)) {
            // É um número, verificar se existe nas opções
            for (let option of select.options) {
                if (parseInt(option.value) === valorInt) {
                    return true;
                }
            }
            return false;
        }
        
        // Se não é um número, é um novo valor (texto digitado)
        return false;
    }

    /**
     * Inicializa submissão do formulário
     */
    function initFormSubmit(form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Mostra loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Salvando...';
            submitBtn.disabled = true;

            try {
                // Cache para evitar criar duplicados se o mesmo nome for usado em múltiplos campos
                const nomesCriadosCache = {};

                /**
                 * Função auxiliar para buscar ou criar e retornar o nome correto
                 * Retorna o nome que deve ser usado (pode ser o digitado ou o nome existente)
                 */
                async function buscarOuCriarNome(tipo, nomeDigitado) {
                    if (!nomeDigitado || nomeDigitado.trim() === '') {
                        return null;
                    }

                    const nomeTrimmed = nomeDigitado.trim();
                    
                    // Verificar no cache primeiro (case-insensitive)
                    const cacheKey = tipo + '_' + nomeTrimmed.toLowerCase();
                    if (nomesCriadosCache[cacheKey]) {
                        return nomesCriadosCache[cacheKey];
                    }

                    // Verificar se já existe na tabela e criar se necessário
                    // O criarRapido já verifica se existe antes de criar
                    try {
                        const resultado = await criarRapido(tipo, nomeTrimmed);
                        // O nome retornado é o nome correto (pode ser o digitado ou o existente)
                        const nomeFinal = resultado.nome;
                        // Armazenar no cache (case-insensitive)
                        nomesCriadosCache[cacheKey] = nomeFinal;
                        return nomeFinal;
                    } catch (error) {
                        console.error('Erro ao buscar/criar ' + tipo + ':', error);
                        throw error;
                    }
                }

                // Processar reclamante_nome (campo de texto que salva o nome)
                const reclamanteNomeSelect = jQuery('#reclamante_nome');
                let reclamanteNome = reclamanteNomeSelect.val();
                if (Array.isArray(reclamanteNome)) {
                    reclamanteNome = reclamanteNome[0];
                }
                
                let reclamanteNomeFinal = reclamanteNome;
                if (reclamanteNome && reclamanteNome.trim() !== '') {
                    try {
                        // Verificar se é um nome novo (não está nas opções do select)
                        let nomeExiste = false;
                        reclamanteNomeSelect.find('option').each(function() {
                            if (jQuery(this).val() === reclamanteNome) {
                                nomeExiste = true;
                                return false; // break
                            }
                        });

                        if (!nomeExiste) {
                            // É um nome novo, buscar ou criar
                            reclamanteNomeFinal = await buscarOuCriarNome('reclamante', reclamanteNome);
                            
                            // Atualizar o select com o nome correto
                            reclamanteNomeSelect.val(reclamanteNomeFinal).trigger('change.select2');
                            await new Promise(resolve => setTimeout(resolve, 100));
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao processar reclamante: ' + error.message
                        });
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                }

                // Processar cliente_nome (campo de texto que salva o nome - é uma Reclamada)
                const clienteNomeSelect = jQuery('#cliente_nome');
                let clienteNome = clienteNomeSelect.val();
                if (Array.isArray(clienteNome)) {
                    clienteNome = clienteNome[0];
                }
                
                let clienteNomeFinal = clienteNome;
                if (clienteNome && clienteNome.trim() !== '') {
                    try {
                        // Verificar se é um nome novo (não está nas opções do select)
                        let nomeExiste = false;
                        clienteNomeSelect.find('option').each(function() {
                            if (jQuery(this).val() === clienteNome) {
                                nomeExiste = true;
                                return false; // break
                            }
                        });

                        if (!nomeExiste) {
                            // É um nome novo, buscar ou criar
                            clienteNomeFinal = await buscarOuCriarNome('reclamada', clienteNome);
                            
                            // Atualizar o select com o nome correto
                            clienteNomeSelect.val(clienteNomeFinal).trigger('change.select2');
                            await new Promise(resolve => setTimeout(resolve, 100));
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao processar cliente/reclamada: ' + error.message
                        });
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                }

                // Verificar se Reclamante do Parecer é novo e criar se necessário
                const reclamanteParecerSelect = jQuery('#parecer_reclamante_id');
                let reclamanteParecerId = reclamanteParecerSelect.val();
                
                // Se for array (Select2 pode retornar array), pegar o primeiro valor
                if (Array.isArray(reclamanteParecerId)) {
                    reclamanteParecerId = reclamanteParecerId[0];
                }
                
                let reclamanteParecerIdFinal = reclamanteParecerId;
                
                // Se o valor não existe nas opções do select ou não é um número, é um novo valor
                if (reclamanteParecerId && (!valorExisteNoSelect('parecer_reclamante_id', reclamanteParecerId) || isNaN(parseInt(reclamanteParecerId)))) {
                    try {
                        // Verificar no cache se já foi criado/verificado
                        const nomeReclamanteParecer = reclamanteParecerId.trim();
                        const cacheKey = 'reclamante_' + nomeReclamanteParecer.toLowerCase();
                        
                        let resultado;
                        if (nomesCriadosCache[cacheKey]) {
                            // Já foi criado/verificado, buscar o ID usando o nome do cache
                            const nomeExistente = nomesCriadosCache[cacheKey];
                            // Buscar o ID do reclamante pelo nome (criarRapido retorna o ID)
                            resultado = await criarRapido('reclamante', nomeExistente);
                        } else {
                            // Criar ou buscar
                            resultado = await criarRapido('reclamante', nomeReclamanteParecer);
                            // Armazenar o nome correto no cache
                            nomesCriadosCache[cacheKey] = resultado.nome;
                        }
                        
                        reclamanteParecerIdFinal = resultado.id.toString();
                        
                        // Remover a opção temporária (texto) se existir
                        reclamanteParecerSelect.find('option').each(function() {
                            const optionValue = jQuery(this).val();
                            // Se o valor da opção é o texto digitado (não é um número)
                            if (optionValue === reclamanteParecerId && isNaN(parseInt(optionValue))) {
                                jQuery(this).remove();
                            }
                        });
                        
                        // Verificar se a opção com o ID já existe antes de adicionar
                        let optionExists = false;
                        reclamanteParecerSelect.find('option').each(function() {
                            if (parseInt(jQuery(this).val()) === resultado.id) {
                                optionExists = true;
                                return false; // break
                            }
                        });
                        
                        if (!optionExists) {
                            // Adicionar a nova opção com ID
                            const newOption = new Option(resultado.nome, resultado.id, true, true);
                            reclamanteParecerSelect.append(newOption);
                        }
                        
                        // Forçar atualização do Select2 com o ID correto
                        reclamanteParecerSelect.val(resultado.id).trigger('change.select2');
                        
                        // Aguardar um pouco para garantir que o Select2 atualizou
                        await new Promise(resolve => setTimeout(resolve, 100));
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao criar reclamante: ' + error.message
                        });
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                }

                // Verificar se Reclamada do Parecer é nova e criar se necessário
                const reclamadaParecerSelect = jQuery('#parecer_reclamada_id');
                let reclamadaParecerId = reclamadaParecerSelect.val();
                
                // Se for array (Select2 pode retornar array), pegar o primeiro valor
                if (Array.isArray(reclamadaParecerId)) {
                    reclamadaParecerId = reclamadaParecerId[0];
                }
                
                let reclamadaParecerIdFinal = reclamadaParecerId;
                
                // Se o valor não existe nas opções do select ou não é um número, é um novo valor
                if (reclamadaParecerId && (!valorExisteNoSelect('parecer_reclamada_id', reclamadaParecerId) || isNaN(parseInt(reclamadaParecerId)))) {
                    try {
                        // Verificar no cache se já foi criado/verificado
                        const nomeReclamadaParecer = reclamadaParecerId.trim();
                        const cacheKey = 'reclamada_' + nomeReclamadaParecer.toLowerCase();
                        
                        let resultado;
                        if (nomesCriadosCache[cacheKey]) {
                            // Já foi criado/verificado, buscar o ID usando o nome do cache
                            const nomeExistente = nomesCriadosCache[cacheKey];
                            // Buscar o ID da reclamada pelo nome (criarRapido retorna o ID)
                            resultado = await criarRapido('reclamada', nomeExistente);
                        } else {
                            // Criar ou buscar
                            resultado = await criarRapido('reclamada', nomeReclamadaParecer);
                            // Armazenar o nome correto no cache
                            nomesCriadosCache[cacheKey] = resultado.nome;
                        }
                        
                        reclamadaParecerIdFinal = resultado.id.toString();
                        
                        // Remover a opção temporária (texto) se existir
                        reclamadaParecerSelect.find('option').each(function() {
                            const optionValue = jQuery(this).val();
                            // Se o valor da opção é o texto digitado (não é um número)
                            if (optionValue === reclamadaParecerId && isNaN(parseInt(optionValue))) {
                                jQuery(this).remove();
                            }
                        });
                        
                        // Verificar se a opção com o ID já existe antes de adicionar
                        let optionExists = false;
                        reclamadaParecerSelect.find('option').each(function() {
                            if (parseInt(jQuery(this).val()) === resultado.id) {
                                optionExists = true;
                                return false; // break
                            }
                        });
                        
                        if (!optionExists) {
                            // Adicionar a nova opção com ID
                            const newOption = new Option(resultado.nome, resultado.id, true, true);
                            reclamadaParecerSelect.append(newOption);
                        }
                        
                        // Forçar atualização do Select2 com o ID correto
                        reclamadaParecerSelect.val(resultado.id).trigger('change.select2');
                        
                        // Aguardar um pouco para garantir que o Select2 atualizou
                        await new Promise(resolve => setTimeout(resolve, 100));
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao criar reclamada: ' + error.message
                        });
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                }

                // Garantir que os valores finais estão corretos nos selects
                reclamanteParecerSelect.val(reclamanteParecerIdFinal).trigger('change.select2');
                reclamadaParecerSelect.val(reclamadaParecerIdFinal).trigger('change.select2');

                // Aguardar um pouco para o Select2 atualizar completamente
                await new Promise(resolve => setTimeout(resolve, 200));

                // Obter os valores finais novamente dos selects (garantir que estão atualizados)
                let reclamanteParecerIdFinalVerificado = reclamanteParecerSelect.val();
                let reclamadaParecerIdFinalVerificado = reclamadaParecerSelect.val();
                
                // Se for array, pegar o primeiro valor
                if (Array.isArray(reclamanteParecerIdFinalVerificado)) {
                    reclamanteParecerIdFinalVerificado = reclamanteParecerIdFinalVerificado[0];
                }
                if (Array.isArray(reclamadaParecerIdFinalVerificado)) {
                    reclamadaParecerIdFinalVerificado = reclamadaParecerIdFinalVerificado[0];
                }
            
                // Prepara dados do formulário
                const formData = new FormData(this);
                
                // Garantir que os nomes corretos estão no formData (reclamante_nome e cliente_nome)
                if (reclamanteNomeFinal) {
                    formData.set('reclamante_nome', reclamanteNomeFinal);
                }
                if (clienteNomeFinal) {
                    formData.set('cliente_nome', clienteNomeFinal);
                }
                
                // Garantir que os IDs corretos estão no formData (não o texto)
                // Converter para número para garantir que é um ID válido
                const reclamanteParecerIdNum = parseInt(reclamanteParecerIdFinalVerificado);
                const reclamadaParecerIdNum = parseInt(reclamadaParecerIdFinalVerificado);
                
                // Se os campos não são obrigatórios, permitir valores vazios
                if (reclamanteParecerIdFinalVerificado && !isNaN(reclamanteParecerIdNum)) {
                    formData.set('parecer_reclamante_id', reclamanteParecerIdNum);
                }
                if (reclamadaParecerIdFinalVerificado && !isNaN(reclamadaParecerIdNum)) {
                    formData.set('parecer_reclamada_id', reclamadaParecerIdNum);
                }
                
                // Faz a requisição
                const action = form.querySelector('input[name="id"]') ? 'edit' : 'criar';
                const url = window.DOMAIN + '/agendamento/' + (action === 'criar' ? 'add/save' : 'edit/save');
                
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = window.DOMAIN + '/agendamento';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: data.message
                    });
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Ocorreu um erro ao salvar o agendamento. Verifique o console do navegador (F12) para mais detalhes.'
                });
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }

})();

