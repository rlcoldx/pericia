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
        
        // Permite selecionar datas passadas também
        // (anteriormente era aplicado min=today no input de data_agendamento)
        // initDateValidation();
        
        // Inicializa Select2 para campos com busca
        initSelect2();

        // Número do processo: busca em outros módulos e preenche partes
        initVinculoProcessoAgendamento();

        // Etapa 1 → Etapa 2: Reclamada, Reclamante e Assistente iguais
        initAgendamentoParecerEtapa1Sync();

        // Data do agendamento → Data da Realização (igual) e Data Fatal (+5 dias)
        initAgendamentoParecerDatesSync(form);
        
        // Inicializa submissão do formulário
        initFormSubmit(form);
    }

    /**
     * Soma dias a uma data YYYY-MM-DD (evita problemas de fuso horário com Date UTC).
     */
    function addDaysToYmd(ymd, days) {
        const parts = ymd.split('-').map(Number);
        if (parts.length !== 3) return '';
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        d.setDate(d.getDate() + days);
        const yy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return yy + '-' + mm + '-' + dd;
    }

    function initAgendamentoParecerDatesSync(form) {
        const dataAgendamento = form.querySelector('input[name="data_agendamento"]');
        const dataRealizacao = form.querySelector('input[name="parecer_data_realizacao"]');
        if (!dataAgendamento || !dataRealizacao) {
            return;
        }

        const dataFatal = form.querySelector('input[name="parecer_data_fatal"]');

        function syncParecerDatesFromAgendamento() {
            const v = dataAgendamento.value;
            if (!v) {
                return;
            }
            dataRealizacao.value = v;
            if (dataFatal) {
                dataFatal.value = addDaysToYmd(v, 5);
            }
        }

        dataAgendamento.addEventListener('change', syncParecerDatesFromAgendamento);
        dataAgendamento.addEventListener('input', syncParecerDatesFromAgendamento);

        // Cadastro novo: se já houver data do agendamento e realização vazia, alinha ao carregar
        if (dataAgendamento.value && String(dataRealizacao.value || '').trim() === '') {
            syncParecerDatesFromAgendamento();
        }
    }

    function initVinculoProcessoAgendamento() {
        if (typeof initVinculoProcesso !== 'function') {
            return;
        }
        const form = document.getElementById('formAgendamento');
        if (!form) {
            return;
        }
        const action = form.getAttribute('data-action') || '';
        const rid = form.getAttribute('data-record-id');
        let excludeId = null;
        if (action === 'editar' && rid) {
            const parsed = parseInt(rid, 10);
            if (!isNaN(parsed)) {
                excludeId = parsed;
            }
        }
        initVinculoProcesso({
            inputSelector: '#numero_processo',
            excludeFonte: action === 'editar' ? 'agendamento' : null,
            excludeId: excludeId,
            reclamada: '#cliente_nome',
            reclamante: '#reclamante_nome',
            perito: '#perito_id',
            assistente: '#assistente_id',
            parecerReclamada: '#parecer_reclamada_id',
            parecerReclamante: '#parecer_reclamante_id',
            parecerAssistente: '#parecer_assistente_id'
        });
    }

    /**
     * Valor único de select/Select2 (tags podem devolver array).
     */
    function getSelect2SingleVal($el) {
        if (!$el || !$el.length) {
            return '';
        }
        let v = $el.val();
        if (Array.isArray(v)) {
            v = v[0];
        }
        return v === undefined || v === null ? '' : String(v);
    }

    /**
     * Copia nome da etapa 1 para o select do parecer (por id ou tag com o mesmo nome).
     */
    function syncParecerNomeParaId(selectId, nome) {
        const $parecer = jQuery('#' + selectId);
        if (!$parecer.length) {
            return;
        }
        const trimmed = String(nome || '').trim();
        if (!trimmed) {
            $parecer.val(null).trigger('change');
            return;
        }
        let matchedVal = null;
        $parecer.find('option').each(function() {
            const $o = jQuery(this);
            const val = $o.val();
            if (val === '' || val === null) {
                return;
            }
            if ($o.text().trim() === trimmed) {
                matchedVal = val;
                return false;
            }
        });
        if (matchedVal !== null) {
            $parecer.val(matchedVal).trigger('change');
            return;
        }
        const jaExiste = $parecer.find('option').filter(function() {
            return jQuery(this).val() === trimmed;
        }).length;
        if (!jaExiste) {
            $parecer.append(new Option(trimmed, trimmed, true, true));
        }
        $parecer.val(trimmed).trigger('change');
    }

    function statusAgendamentoEhParecerRevisar() {
        const el = document.getElementById('agendamento_status');
        return el && el.value === 'Parecer para Revisar';
    }

    /**
     * Copia o assistente do 1º passo para o select do parecer (2º passo), criando a opção se faltar.
     */
    function copiarAssistentePrimeiroPassoParaParecer() {
        if (typeof jQuery === 'undefined') {
            return;
        }
        const $a = jQuery('#assistente_id');
        const $pa = jQuery('#parecer_assistente_id');
        if (!$a.length || !$pa.length) {
            return;
        }
        const vRaw = getSelect2SingleVal($a);
        if (!vRaw) {
            return;
        }
        const v = String(vRaw).trim();
        const vid = parseInt(v, 10);
        const isNumericId = !isNaN(vid) && String(vid) === v;

        if (!isNumericId) {
            let exists = false;
            $pa.find('option').each(function() {
                if (String(jQuery(this).val()) === v) {
                    exists = true;
                    return false;
                }
            });
            if (!exists) {
                const txt = $a.find('option:selected').text() || v;
                $pa.append(new Option(txt, v, true, true));
            }
            $pa.val(v).trigger('change');
            return;
        }

        let optExists = false;
        $pa.find('option').each(function() {
            if (parseInt(jQuery(this).val(), 10) === vid) {
                optExists = true;
                return false;
            }
        });
        if (!optExists) {
            let nome = $a.find('option[value="' + vid + '"]').text();
            if (!nome) {
                nome = $a.find('option:selected').text();
            }
            $pa.append(new Option(nome || ('ID ' + vid), vid, true, true));
        }
        $pa.val(String(vid)).trigger('change');
    }

    /**
     * Reclamada/Reclamante: espelha nomes → IDs no parecer.
     * Assistente: só espelha no 2º passo quando status = Parecer para Revisar.
     */
    function initAgendamentoParecerEtapa1Sync() {
        if (typeof jQuery === 'undefined') {
            return;
        }

        const $assistente = jQuery('#assistente_id');
        const $parecerAssistente = jQuery('#parecer_assistente_id');
        if ($assistente.length && $parecerAssistente.length) {
            $assistente.on('change', function() {
                if (statusAgendamentoEhParecerRevisar()) {
                    copiarAssistentePrimeiroPassoParaParecer();
                }
            });
        }

        const statusEl = document.getElementById('agendamento_status');
        if (statusEl) {
            statusEl.addEventListener('change', function() {
                if (this.value === 'Parecer para Revisar') {
                    copiarAssistentePrimeiroPassoParaParecer();
                }
            });
            if (statusEl.value === 'Parecer para Revisar') {
                setTimeout(copiarAssistentePrimeiroPassoParaParecer, 0);
            }
        }

        const $cliente = jQuery('#cliente_nome');
        if ($cliente.length) {
            $cliente.on('change', function() {
                syncParecerNomeParaId('parecer_reclamada_id', getSelect2SingleVal($cliente));
            });
        }

        const $reclamante = jQuery('#reclamante_nome');
        if ($reclamante.length) {
            $reclamante.on('change', function() {
                syncParecerNomeParaId('parecer_reclamante_id', getSelect2SingleVal($reclamante));
            });
        }
    }

    /**
     * Inicializa Select2 para campos com busca (Assistente, Perito, etc)
     */
    function initSelect2() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            return;
        }

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

        const numeroProcessoSelect = jQuery('#numero_processo');
        if (numeroProcessoSelect.length) {
            numeroProcessoSelect.select2({
                tags: true,
                placeholder: 'Selecione um número ou digite um novo',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return 'Digite para informar um novo número';
                    },
                    searching: function() {
                        return 'Buscando...';
                    }
                }
            });
        }

        // Select2 para Perito com tags (permite criar novos)
        const peritoSelect = jQuery('#perito_id');
        if (peritoSelect.length) {
            peritoSelect.select2({
                tags: true,
                placeholder: 'Selecione ou digite para criar um novo Perito',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Digite para criar um novo perito";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        }

        // Select2 para Assistente (1º passo) e Assistente do Parecer (2º passo) — tags como Perito/Reclamada
        ['#assistente_id', '#parecer_assistente_id'].forEach(function(selector) {
            const assistSelect = jQuery(selector);
            if (assistSelect.length) {
                assistSelect.select2({
                    tags: true,
                    placeholder: 'Selecione ou digite para criar um novo Assistente',
                    allowClear: true,
                    width: '100%',
                    language: {
                        noResults: function() {
                            return "Digite para criar um novo assistente";
                        },
                        searching: function() {
                            return "Buscando...";
                        }
                    }
                });
            }
        });
    }



    // function initDateValidation() {
    //     const dataInput = document.querySelector('input[name="data_agendamento"]');
    //     if (dataInput) {
    //         const hoje = new Date().toISOString().split('T')[0];
    //         dataInput.setAttribute('min', hoje);
    //     }
    // }

    /**
     * Cria um reclamante/reclamada/perito rapidamente via AJAX
     */
    async function criarRapido(tipo, nome) {
        let endpoint;
        if (tipo === 'reclamante') {
            endpoint = window.DOMAIN + '/reclamantes/criar-rapido';
        } else if (tipo === 'reclamada') {
            endpoint = window.DOMAIN + '/reclamadas/criar-rapido';
        } else if (tipo === 'perito') {
            endpoint = window.DOMAIN + '/perito/criar-rapido';
        } else if (tipo === 'assistente') {
            endpoint = window.DOMAIN + '/assistentes/criar-rapido';
        } else {
            throw new Error('Tipo inválido para criação rápida');
        }
        
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

                // Verificar se Perito é novo e criar se necessário
                const peritoSelect = jQuery('#perito_id');
                let peritoId = peritoSelect.val();

                if (Array.isArray(peritoId)) {
                    peritoId = peritoId[0];
                }

                let peritoIdFinal = peritoId;

                // Se o valor não existe nas opções do select ou não é um número, é um novo valor
                if (peritoId && (!valorExisteNoSelect('perito_id', peritoId) || isNaN(parseInt(peritoId)))) {
                    try {
                        const resultadoPerito = await criarRapido('perito', peritoId);
                        peritoIdFinal = resultadoPerito.id.toString();

                        // Remover a opção temporária (texto) se existir
                        peritoSelect.find('option').each(function() {
                            const optionValue = jQuery(this).val();
                            if (optionValue === peritoId && isNaN(parseInt(optionValue))) {
                                jQuery(this).remove();
                            }
                        });

                        // Verificar se a opção com o ID já existe antes de adicionar
                        let optionPeritoExists = false;
                        peritoSelect.find('option').each(function() {
                            if (parseInt(jQuery(this).val()) === resultadoPerito.id) {
                                optionPeritoExists = true;
                                return false; // break
                            }
                        });

                        if (!optionPeritoExists) {
                            const newPeritoOption = new Option(resultadoPerito.nome, resultadoPerito.id, true, true);
                            peritoSelect.append(newPeritoOption);
                        }

                        // Forçar atualização do Select2 com o ID correto
                        peritoSelect.val(resultadoPerito.id).trigger('change.select2');

                        await new Promise(resolve => setTimeout(resolve, 100));
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao criar perito: ' + error.message
                        });
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                }

                const assistenteSelect = jQuery('#assistente_id');
                const parecerAssistenteSelect = jQuery('#parecer_assistente_id');

                let assistenteVal = assistenteSelect.length ? assistenteSelect.val() : null;
                if (Array.isArray(assistenteVal)) {
                    assistenteVal = assistenteVal[0];
                }
                let assistenteIdFinal = assistenteVal;

                if (assistenteSelect.length && assistenteVal && (!valorExisteNoSelect('assistente_id', assistenteVal) || isNaN(parseInt(assistenteVal, 10)))) {
                    try {
                        const resultadoAss = await criarRapido('assistente', String(assistenteVal).trim());
                        assistenteIdFinal = resultadoAss.id.toString();

                        assistenteSelect.find('option').each(function() {
                            const optionValue = jQuery(this).val();
                            if (optionValue === assistenteVal && isNaN(parseInt(optionValue, 10))) {
                                jQuery(this).remove();
                            }
                        });

                        let optionAssExists = false;
                        assistenteSelect.find('option').each(function() {
                            if (parseInt(jQuery(this).val(), 10) === resultadoAss.id) {
                                optionAssExists = true;
                                return false;
                            }
                        });

                        if (!optionAssExists) {
                            assistenteSelect.append(new Option(resultadoAss.nome, resultadoAss.id, true, true));
                        }

                        assistenteSelect.val(resultadoAss.id).trigger('change.select2');

                        if (parecerAssistenteSelect.length) {
                            let paOptExists = false;
                            parecerAssistenteSelect.find('option').each(function() {
                                if (parseInt(jQuery(this).val(), 10) === resultadoAss.id) {
                                    paOptExists = true;
                                    return false;
                                }
                            });
                            if (!paOptExists) {
                                parecerAssistenteSelect.append(new Option(resultadoAss.nome, resultadoAss.id, true, true));
                            }
                        }

                        await new Promise(resolve => setTimeout(resolve, 100));
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao criar assistente: ' + error.message
                        });
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                }

                let paVal = parecerAssistenteSelect.length ? parecerAssistenteSelect.val() : null;
                if (Array.isArray(paVal)) {
                    paVal = paVal[0];
                }
                let parecerAssistenteIdFinal = paVal;

                if (parecerAssistenteSelect.length && paVal && (!valorExisteNoSelect('parecer_assistente_id', paVal) || isNaN(parseInt(paVal, 10)))) {
                    try {
                        const resultadoPa = await criarRapido('assistente', String(paVal).trim());
                        parecerAssistenteIdFinal = resultadoPa.id.toString();

                        parecerAssistenteSelect.find('option').each(function() {
                            const optionValue = jQuery(this).val();
                            if (optionValue === paVal && isNaN(parseInt(optionValue, 10))) {
                                jQuery(this).remove();
                            }
                        });

                        let optionPaExists = false;
                        parecerAssistenteSelect.find('option').each(function() {
                            if (parseInt(jQuery(this).val(), 10) === resultadoPa.id) {
                                optionPaExists = true;
                                return false;
                            }
                        });

                        if (!optionPaExists) {
                            parecerAssistenteSelect.append(new Option(resultadoPa.nome, resultadoPa.id, true, true));
                        }

                        parecerAssistenteSelect.val(resultadoPa.id).trigger('change.select2');

                        if (assistenteSelect.length) {
                            let aOptExists = false;
                            assistenteSelect.find('option').each(function() {
                                if (parseInt(jQuery(this).val(), 10) === resultadoPa.id) {
                                    aOptExists = true;
                                    return false;
                                }
                            });
                            if (!aOptExists) {
                                assistenteSelect.append(new Option(resultadoPa.nome, resultadoPa.id, true, true));
                            }
                        }

                        await new Promise(resolve => setTimeout(resolve, 100));
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao criar assistente (parecer): ' + error.message
                        });
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                }

                // Garantir que os valores finais estão corretos nos selects
                reclamanteParecerSelect.val(reclamanteParecerIdFinal).trigger('change.select2');
                reclamadaParecerSelect.val(reclamadaParecerIdFinal).trigger('change.select2');
                peritoSelect.val(peritoIdFinal).trigger('change.select2');
                if (assistenteSelect.length) {
                    assistenteSelect.val(assistenteIdFinal).trigger('change.select2');
                }
                if (parecerAssistenteSelect.length) {
                    parecerAssistenteSelect.val(parecerAssistenteIdFinal).trigger('change.select2');
                }

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

                const $numeroProcesso = jQuery('#numero_processo');
                if ($numeroProcesso.length) {
                    let npVal = $numeroProcesso.val();
                    if (Array.isArray(npVal)) {
                        npVal = npVal[0];
                    }
                    if (npVal !== undefined && npVal !== null) {
                        formData.set('numero_processo', npVal);
                    }
                }

                const horaInput = form.querySelector('input[name="hora_agendamento"]');
                if (!horaInput || !String(horaInput.value || '').trim()) {
                    formData.set('hora_agendamento', '09:00');
                }

                const $peritoField = jQuery('#perito_id');
                if ($peritoField.length) {
                    let pVal = $peritoField.val();
                    if (Array.isArray(pVal)) {
                        pVal = pVal[0];
                    }
                    if (pVal !== undefined && pVal !== null && pVal !== '') {
                        formData.set('perito_id', pVal);
                    }
                }

                const $assistenteField = jQuery('#assistente_id');
                if ($assistenteField.length) {
                    let aVal = $assistenteField.val();
                    if (Array.isArray(aVal)) {
                        aVal = aVal[0];
                    }
                    if (aVal !== undefined && aVal !== null && aVal !== '') {
                        formData.set('assistente_id', aVal);
                    }
                }

                const $parecerAssistenteField = jQuery('#parecer_assistente_id');
                if ($parecerAssistenteField.length) {
                    let pav = $parecerAssistenteField.val();
                    if (Array.isArray(pav)) {
                        pav = pav[0];
                    }
                    if (pav !== undefined && pav !== null && pav !== '') {
                        formData.set('parecer_assistente_id', pav);
                    }
                }
                
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
                
                const responseText = await response.text();
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseErr) {
                    const preview = responseText.replace(/\s+/g, ' ').trim().substring(0, 280);
                    throw new Error(
                        response.ok
                            ? ('Resposta inválida do servidor' + (preview ? ': ' + preview : ''))
                            : ('HTTP ' + response.status + (preview ? ' — ' + preview : ''))
                    );
                }
                
                if (!response.ok) {
                    throw new Error(data.message || ('HTTP error! status: ' + response.status));
                }
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        const path = typeof data.redirect === 'string' ? data.redirect : '/agendamento';
                        window.location.href = window.DOMAIN + path;
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
                const msgErro = (error && error.message) ? error.message : 'Ocorreu um erro ao salvar o agendamento. Verifique o console (F12) para mais detalhes.';
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: msgErro
                });
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }

})();

