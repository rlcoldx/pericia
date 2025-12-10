/**
 * Home Dashboard - Estatísticas e Gráficos
 */

(function() {
    'use strict';

    let charts = {};
    let currentData = window.STATISTICS_DATA || {};

    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts não está carregado');
            return;
        }

        setupFilterForm();
        renderAllCharts();
    }

    function setupFilterForm() {
        const form = document.getElementById('filtroDataForm');
        if (!form) return;

        // Botão "Mês Atual"
        const btnMesAtual = document.getElementById('btnMesAtual');
        if (btnMesAtual) {
            btnMesAtual.addEventListener('click', function() {
                const hoje = new Date();
                const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
                const ultimoDia = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);

                document.getElementById('data_inicio').value = primeiroDia.toISOString().split('T')[0];
                document.getElementById('data_fim').value = ultimoDia.toISOString().split('T')[0];
                
                form.submit();
            });
        }

        // Submissão do formulário
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            loadStatistics();
        });
    }

    function loadStatistics() {
        const form = document.getElementById('filtroDataForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();

        // Desabilitar botões
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Carregando...';
        submitBtn.disabled = true;

        fetch(window.DOMAIN + '/home/estatisticas?' + params)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentData = data.data;
                    updateStatisticsCards();
                    updateAllCharts();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao carregar estatísticas.'
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao carregar estatísticas.'
                });
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
    }

    function updateStatisticsCards() {
        // Quesitos
        const quesitosTotal = document.getElementById('stat-quesitos-total');
        if (quesitosTotal) {
            quesitosTotal.textContent = currentData.quesitos?.total || 0;
        }

        // Manifestações
        const manifestacoesTotal = document.getElementById('stat-manifestacoes-total');
        if (manifestacoesTotal) {
            manifestacoesTotal.textContent = currentData.manifestacoes?.total || 0;
        }

        // Pareceres
        const pareceresTotal = document.getElementById('stat-pareceres-total');
        if (pareceresTotal) {
            pareceresTotal.textContent = currentData.pareceres?.total || 0;
        }

        // Agendamentos
        const agendamentosRealizados = document.getElementById('stat-agendamentos-realizados');
        if (agendamentosRealizados) {
            agendamentosRealizados.textContent = currentData.agendamentos?.realizados || 0;
        }

        // Financeiro
        const financeiroTotal = document.getElementById('stat-financeiro-total');
        if (financeiroTotal) {
            const valor = currentData.financeiro?.valor_total || 0;
            financeiroTotal.textContent = 'R$ ' + valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        const financeiroPago = document.getElementById('stat-financeiro-pago');
        if (financeiroPago) {
            const valor = currentData.financeiro?.valor_pago || 0;
            financeiroPago.textContent = 'R$ ' + valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    function renderAllCharts() {
        renderQuesitosStatusChart();
        renderQuesitosDiaChart();
        renderManifestacoesDiaChart();
        renderAgendamentosStatusChart();
        renderFinanceiroStatusChart();
    }

    function updateAllCharts() {
        Object.values(charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        charts = {};
        renderAllCharts();
    }

    // Quesitos por Status (Donut)
    function renderQuesitosStatusChart() {
        const el = document.getElementById('chartQuesitosStatus');
        if (!el) return;

        const porStatus = currentData.quesitos?.por_status || [];
        const labels = porStatus.map(item => item.status);
        const series = porStatus.map(item => parseInt(item.total));

        if (series.length === 0) {
            el.innerHTML = '<div class="text-center p-5"><p>Nenhum dado disponível</p></div>';
            return;
        }

        const options = {
            series: series,
            chart: {
                type: 'donut',
                height: 350
            },
            labels: labels,
            colors: ['#d6a220', '#45ADDA', '#FE634E', '#FFC700', '#28a745'],
            legend: {
                position: 'bottom'
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(0) + '%';
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%'
                    }
                }
            }
        };

        charts.quesitosStatus = new ApexCharts(el, options);
        charts.quesitosStatus.render();
    }

    // Quesitos por Dia (Line)
    function renderQuesitosDiaChart() {
        const el = document.getElementById('chartQuesitosDia');
        if (!el) return;

        const porDia = currentData.quesitos?.por_dia || [];
        const dias = Array.from({ length: 31 }, (_, i) => i + 1);
        const valores = dias.map(dia => {
            const item = porDia.find(d => parseInt(d.dia) === dia);
            return item ? parseInt(item.total) : 0;
        });

        const options = {
            series: [{
                name: 'Quesitos',
                data: valores
            }],
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: false }
            },
            colors: ['#d6a220'],
            stroke: {
                curve: 'smooth',
                width: 3
            },
            xaxis: {
                categories: dias.map(d => d.toString()),
                title: { text: 'Dia do Mês' }
            },
            yaxis: {
                title: { text: 'Quantidade' }
            },
            dataLabels: {
                enabled: false
            },
            grid: {
                show: true,
                borderColor: '#e0e0e0'
            }
        };

        charts.quesitosDia = new ApexCharts(el, options);
        charts.quesitosDia.render();
    }

    // Manifestações por Dia (Bar)
    function renderManifestacoesDiaChart() {
        const el = document.getElementById('chartManifestacoesDia');
        if (!el) return;

        const porDia = currentData.manifestacoes?.por_dia || [];
        const dias = Array.from({ length: 31 }, (_, i) => i + 1);
        const valores = dias.map(dia => {
            const item = porDia.find(d => parseInt(d.dia) === dia);
            return item ? parseInt(item.total) : 0;
        });

        const options = {
            series: [{
                name: 'Manifestações',
                data: valores
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false }
            },
            colors: ['#45ADDA'],
            xaxis: {
                categories: dias.map(d => d.toString()),
                title: { text: 'Dia do Mês' }
            },
            yaxis: {
                title: { text: 'Quantidade' }
            },
            dataLabels: {
                enabled: false
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '50%'
                }
            }
        };

        charts.manifestacoesDia = new ApexCharts(el, options);
        charts.manifestacoesDia.render();
    }

    // Agendamentos por Status (Donut)
    function renderAgendamentosStatusChart() {
        const el = document.getElementById('chartAgendamentosStatus');
        if (!el) return;

        const porStatus = currentData.agendamentos?.por_status || [];
        const labels = porStatus.map(item => item.status);
        const series = porStatus.map(item => parseInt(item.total));

        if (series.length === 0) {
            el.innerHTML = '<div class="text-center p-5"><p>Nenhum dado disponível</p></div>';
            return;
        }

        const options = {
            series: series,
            chart: {
                type: 'donut',
                height: 350
            },
            labels: labels,
            colors: ['#FFC700', '#28a745', '#FE634E', '#6c757d'],
            legend: {
                position: 'bottom'
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(0) + '%';
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%'
                    }
                }
            }
        };

        charts.agendamentosStatus = new ApexCharts(el, options);
        charts.agendamentosStatus.render();
    }

    // Financeiro por Status (Bar)
    function renderFinanceiroStatusChart() {
        const el = document.getElementById('chartFinanceiroStatus');
        if (!el) return;

        const porStatus = currentData.financeiro?.por_status || [];
        const labels = porStatus.map(item => item.status);
        const valores = porStatus.map(item => parseFloat(item.valor_total || 0));

        if (valores.length === 0) {
            el.innerHTML = '<div class="text-center p-5"><p>Nenhum dado disponível</p></div>';
            return;
        }

        const options = {
            series: [{
                name: 'Valor (R$)',
                data: valores
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            colors: ['#FFC700'],
            xaxis: {
                categories: labels
            },
            yaxis: {
                title: { text: 'Valor (R$)' },
                labels: {
                    formatter: function(val) {
                        return 'R$ ' + val.toLocaleString('pt-BR');
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return 'R$ ' + parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '50%'
                }
            }
        };

        charts.financeiroStatus = new ApexCharts(el, options);
        charts.financeiroStatus.render();
    }

})();
