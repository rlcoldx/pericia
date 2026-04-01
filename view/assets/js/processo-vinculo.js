/**
 * Preenche Reclamada, Reclamante e campos opcionais a partir do número do processo
 * (busca em agendamentos, quesitos, manifestações e pareceres).
 */
(function() {
    'use strict';

    function setSelectOuTags($sel, id, nome) {
        if (!$sel || !$sel.length) {
            return;
        }
        if (id) {
            $sel.val(String(id)).trigger('change');
            return;
        }
        if (!nome || String(nome).trim() === '') {
            return;
        }
        var n = String(nome).trim();
        var found = false;
        $sel.find('option').each(function() {
            var $o = jQuery(this);
            if ($o.val() === '' || $o.val() === null) {
                return;
            }
            if ($o.text().trim() === n) {
                $sel.val($o.val()).trigger('change');
                found = true;
                return false;
            }
        });
        if (!found) {
            var hasOpt = $sel.find('option').filter(function() {
                return jQuery(this).val() === n;
            }).length;
            if (!hasOpt) {
                $sel.append(new Option(n, n, true, true));
            }
            $sel.val(n).trigger('change');
        }
    }

    function aplicar(config, d) {
        if (typeof jQuery === 'undefined') {
            return;
        }
        if (config.reclamada) {
            setSelectOuTags(jQuery(config.reclamada), d.reclamada_id || null, d.reclamada_nome || null);
        }
        if (config.reclamante) {
            setSelectOuTags(jQuery(config.reclamante), d.reclamante_id || null, d.reclamante_nome || null);
        }
        if (config.vara && d.vara) {
            jQuery(config.vara).val(d.vara);
        }
        if (config.perito && d.perito_id) {
            jQuery(config.perito).val(String(d.perito_id)).trigger('change');
        }
        if (config.assistente && d.assistente_id) {
            jQuery(config.assistente).val(String(d.assistente_id)).trigger('change');
        }
        if (config.parecerReclamada && d.reclamada_id) {
            setSelectOuTags(jQuery(config.parecerReclamada), d.reclamada_id, d.reclamada_nome || null);
        }
        if (config.parecerReclamante && d.reclamante_id) {
            setSelectOuTags(jQuery(config.parecerReclamante), d.reclamante_id, d.reclamante_nome || null);
        }
        if (config.parecerAssistente && d.assistente_id) {
            jQuery(config.parecerAssistente).val(String(d.assistente_id)).trigger('change');
        }
    }

    window.initVinculoProcesso = function(config) {
        if (!config || !config.inputSelector) {
            return;
        }
        if (typeof jQuery === 'undefined') {
            return;
        }
        var $el = jQuery(config.inputSelector);
        if (!$el.length) {
            return;
        }

        var busy = false;
        function getNumero() {
            var v = $el.val();
            if (Array.isArray(v)) {
                v = v[0];
            }
            return String(v || '').trim();
        }
        function buscar() {
            if (busy) {
                return;
            }
            var numero = getNumero();
            if (!numero) {
                return;
            }

            busy = true;
            var base = typeof window.DOMAIN !== 'undefined' ? window.DOMAIN : '';
            var url = base + '/vinculo-processo/buscar?numero=' + encodeURIComponent(numero);
            if (config.excludeFonte && config.excludeId) {
                url += '&exclude_fonte=' + encodeURIComponent(config.excludeFonte) +
                    '&exclude_id=' + encodeURIComponent(String(config.excludeId));
            }

            fetch(url, { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    busy = false;
                    if (!data.success || !data.found || !data.dados) {
                        return;
                    }
                    aplicar(config, data.dados);
                })
                .catch(function() {
                    busy = false;
                });
        }

        if ($el.is('select')) {
            $el.on('change', buscar);
        } else {
            var input = $el[0];
            if (input) {
                input.addEventListener('blur', buscar);
            }
        }
    };
})();
