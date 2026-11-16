(function () {
    'use strict';

    function formatarDigitos(digitos) {
        if (!digitos) {
            return '';
        }
        digitos = digitos.slice(0, 12).replace(/^0+(?=\d)/, '');
        var centavos = digitos.length < 2 ? digitos.padStart(2, '0') : digitos.slice(-2);
        var reais = digitos.length < 2 ? '0' : digitos.slice(0, -2);
        return reais.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + centavos;
    }

    function paraNumero(texto) {
        if (!texto) {
            return 0;
        }
        var limpo = String(texto).replace(/[^\d,.-]/g, '');
        if (limpo.indexOf(',') !== -1) {
            limpo = limpo.replace(/\./g, '').replace(',', '.');
        }
        var numero = parseFloat(limpo);
        return isNaN(numero) ? 0 : numero;
    }

    function paraTexto(numero) {
        return numero.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var campos = document.querySelectorAll('input.apreas-valor:not([readonly])');
        var total = document.getElementById('valor_total');
        var terceiro = document.getElementById('valor_terceiro');
        var receber = document.getElementById('valor_receber');

        function atualizarReceber() {
            if (!receber) {
                return;
            }
            receber.value = paraTexto(paraNumero(total ? total.value : '') - paraNumero(terceiro ? terceiro.value : ''));
        }

        campos.forEach(function (campo) {
            campo.addEventListener('input', function () {
                campo.value = formatarDigitos(campo.value.replace(/\D/g, ''));
                atualizarReceber();
            });
        });

        atualizarReceber();

        var horas = document.getElementById('quantidade_horas');
        var minutos = document.getElementById('quantidade_minutos');
        var resumo = document.getElementById('apreas-duracao-resumo');

        function totalMinutos() {
            var h = parseInt(horas ? horas.value : '', 10) || 0;
            var m = parseInt(minutos ? minutos.value : '', 10) || 0;
            return Math.max(0, h) * 60 + Math.max(0, m);
        }

        function atualizarResumo() {
            if (!resumo) {
                return;
            }
            var total = totalMinutos();
            resumo.textContent = total > 0
                ? '▸ Duração: ' + Math.floor(total / 60) + 'h' + String(total % 60).padStart(2, '0') + ' no Google Calendar.'
                : '▸ Horas e minutos definem a duração do evento no Google Calendar.';
        }

        function normalizarDuracao() {
            if (!horas || !minutos) {
                return;
            }
            if (horas.value === '' && minutos.value === '') {
                return;
            }
            var total = totalMinutos();
            horas.value = String(Math.floor(total / 60));
            minutos.value = String(total % 60);
        }

        if (horas && minutos) {
            [horas, minutos].forEach(function (campo) {
                campo.addEventListener('input', atualizarResumo);
                campo.addEventListener('change', function () {
                    normalizarDuracao();
                    atualizarResumo();
                });
            });
            atualizarResumo();
        }
    });
})();