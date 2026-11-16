(function () {
    'use strict';

    var cfg = window.ApreasGcal || {};
    var raiz = document.getElementById('apreas-gcal');
    if (!raiz || !cfg.ajaxUrl || !cfg.nonce) {
        return;
    }

    var campoJson = raiz.querySelector('[data-json]');
    var campoCalendar = raiz.querySelector('[data-calendar-id]');
    var checkEnabled = document.getElementById('apreas_gcal_enabled');
    var btnTestar = raiz.querySelector('[data-testar]');
    var btnSalvar = raiz.querySelector('[data-salvar]');
    var textoTestar = raiz.querySelector('[data-testar-texto]');
    var textoSalvar = raiz.querySelector('[data-salvar-texto]');
    var iconeTestar = raiz.querySelector('[data-icone-testar]');
    var iconeSalvar = raiz.querySelector('[data-icone-salvar]');
    var painel = raiz.querySelector('[data-resultado]');
    var pill = raiz.parentNode.querySelector('[data-status-geral]');
    var indicadorPendente = raiz.querySelector('[data-pendente]');
    var blocoUltimo = raiz.querySelector('[data-ultimo]');
    var textoUltimo = raiz.querySelector('[data-ultimo-texto]');
    var blocoIdentidade = raiz.querySelector('[data-identidade]');
    var blocoEmailServico = raiz.querySelector('[data-email-servico]');

    var estado = {
        json: { ok: false, dados: null },
        calendar: { ok: false },
        foco: 0,
        passo3: 'pending',
        testando: false,
        salvando: false,
        configurado: !!cfg.configurado,
        ultimoTeste: cfg.ultimoTeste || null,
        salvo: {
            json: campoJson.value,
            calendar: campoCalendar.value,
            enabled: checkEnabled ? checkEnabled.checked : true
        }
    };

    var timersEstagio = [];

    function setIcone(el, nome, girar) {
        el.className = 'dashicons dashicons-' + nome + (girar ? ' apreas-gcal-girar' : '');
    }

    function setStep(n, situacao) {
        var li = raiz.querySelector('[data-step="' + n + '"]');
        var bolinha = raiz.querySelector('[data-bolinha="' + n + '"]');
        if (!li || !bolinha) {
            return;
        }
        li.classList.remove('is-active', 'is-done', 'is-error', 'is-aviso');
        bolinha.textContent = '';
        if (situacao === 'done') {
            li.classList.add('is-done');
            var ok = document.createElement('span');
            ok.className = 'dashicons dashicons-yes';
            bolinha.appendChild(ok);
        } else if (situacao === 'error') {
            li.classList.add('is-error');
            bolinha.textContent = '!';
        } else if (situacao === 'aviso') {
            li.classList.add('is-aviso');
            bolinha.textContent = '!';
        } else {
            bolinha.textContent = String(n);
            if (situacao === 'active') {
                li.classList.add('is-active');
            }
        }
    }

    function setChip(n, classe, texto) {
        var chip = raiz.querySelector('[data-chip="' + n + '"]');
        if (!chip) {
            return;
        }
        chip.className = 'apreas-gcal-chip' + (classe ? ' ' + classe : '');
        chip.textContent = texto;
    }

    function setFeedback(n, tipo, icone, texto) {
        var bloco = raiz.querySelector('[data-feedback="' + n + '"]');
        var alvo = raiz.querySelector('[data-feedback-texto="' + n + '"]');
        if (!bloco || !alvo) {
            return;
        }
        bloco.classList.remove('is-ok', 'is-erro');
        if (tipo) {
            bloco.classList.add(tipo);
        }
        setIcone(bloco.querySelector('.dashicons'), icone, false);
        alvo.textContent = texto;
    }

    function validarJson() {
        var texto = campoJson.value.trim();
        if (!texto) {
            return { status: 'vazio', mensagem: 'Aguardando a chave JSON.' };
        }

        var obj;
        try {
            obj = JSON.parse(texto);
        } catch (e) {
            return { status: 'erro', mensagem: 'JSON inválido: copie o arquivo inteiro, da primeira "{" à última "}".' };
        }

        if (!obj || typeof obj !== 'object' || Array.isArray(obj)) {
            return { status: 'erro', mensagem: 'O JSON precisa ser o objeto da service account, não uma lista ou texto solto.' };
        }
        if (obj.type && obj.type !== 'service_account') {
            return { status: 'erro', mensagem: 'Este JSON é do tipo "' + obj.type + '", não uma service account. Crie a chave em IAM e administrador → Contas de serviço → Chaves.' };
        }

        var faltando = [];
        if (!obj.client_email) {
            faltando.push('client_email');
        }
        if (!obj.private_key) {
            faltando.push('private_key');
        }
        if (faltando.length) {
            return { status: 'erro', mensagem: 'Faltam campos no JSON: ' + faltando.join(', ') + '. Cole o arquivo completo da service account.' };
        }
        if (String(obj.private_key).indexOf('BEGIN PRIVATE KEY') === -1) {
            return { status: 'erro', mensagem: 'A private_key está incompleta (não contém "BEGIN PRIVATE KEY"). Copie o arquivo inteiro, sem editar as quebras de linha.' };
        }

        return {
            status: 'ok',
            mensagem: 'Chave válida. Compartilhe o calendário com o e-mail abaixo (passo 2).',
            dados: {
                client_email: String(obj.client_email),
                project_id: obj.project_id ? String(obj.project_id) : '—'
            }
        };
    }

    function validarCalendar() {
        var valor = campoCalendar.value.trim();
        if (!valor) {
            return { status: 'vazio', mensagem: 'Aguardando o Calendar ID.' };
        }
        if (/\s/.test(valor)) {
            return { status: 'erro', mensagem: 'O Calendar ID não pode conter espaços.' };
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor)) {
            return { status: 'erro', mensagem: 'Formato inesperado: use o e-mail da conta Google (calendário principal) ou o ID terminado em @group.calendar.google.com.' };
        }
        return { status: 'ok', mensagem: 'Calendar ID válido. O calendário precisa estar compartilhado com a service account.' };
    }

    function atualizarPasso1() {
        var r = validarJson();
        estado.json = { ok: r.status === 'ok', dados: r.dados || null };

        if (r.status === 'ok') {
            setStep(1, 'done');
            setChip(1, 'is-ok', 'OK');
            setFeedback(1, 'is-ok', 'yes-alt', r.mensagem);
        } else if (r.status === 'erro') {
            setStep(1, 'error');
            setChip(1, 'is-erro', 'Inválido');
            setFeedback(1, 'is-erro', 'no-alt', r.mensagem);
        } else {
            setStep(1, estado.foco === 1 ? 'active' : 'pending');
            setChip(1, '', 'Pendente');
            setFeedback(1, '', 'info', r.mensagem);
        }

        if (estado.json.dados) {
            blocoIdentidade.hidden = false;
            blocoEmailServico.hidden = false;
            raiz.querySelector('[data-client-email]').textContent = estado.json.dados.client_email;
            raiz.querySelector('[data-client-email-2]').textContent = estado.json.dados.client_email;
            raiz.querySelector('[data-project-id]').textContent = estado.json.dados.project_id;
        } else {
            blocoIdentidade.hidden = true;
            blocoEmailServico.hidden = true;
        }

        atualizarBotaoTestar();
        atualizarPendente();
    }

    function atualizarPasso2() {
        var r = validarCalendar();
        estado.calendar.ok = r.status === 'ok';

        if (r.status === 'ok') {
            setStep(2, 'done');
            setChip(2, 'is-ok', 'OK');
            setFeedback(2, 'is-ok', 'yes-alt', r.mensagem);
        } else if (r.status === 'erro') {
            setStep(2, 'error');
            setChip(2, 'is-erro', 'Inválido');
            setFeedback(2, 'is-erro', 'no-alt', r.mensagem);
        } else {
            setStep(2, estado.foco === 2 ? 'active' : 'pending');
            setChip(2, '', 'Pendente');
            setFeedback(2, '', 'info', r.mensagem);
        }

        atualizarBotaoTestar();
        atualizarPendente();
    }

    function atualizarPasso3() {
        if (estado.testando) {
            setStep(3, 'active');
            setChip(3, '', 'Testando…');
            return;
        }
        if (estado.passo3 === 'done') {
            setStep(3, 'done');
            setChip(3, 'is-ok', 'Conectado');
        } else if (estado.passo3 === 'aviso') {
            setStep(3, 'aviso');
            setChip(3, 'is-aviso', 'Somente leitura');
        } else if (estado.passo3 === 'error') {
            setStep(3, 'error');
            setChip(3, 'is-erro', 'Falhou');
        } else if (estado.json.ok && estado.calendar.ok) {
            setStep(3, 'active');
            setChip(3, '', 'Pronto para testar');
        } else {
            setStep(3, 'pending');
            setChip(3, '', 'Aguardando passos 1 e 2');
        }
    }

    function atualizarBotaoTestar() {
        btnTestar.disabled = estado.testando || !estado.json.ok || !estado.calendar.ok;
        atualizarPasso3();
    }

    function atualizarPendente() {
        var sujo = campoJson.value !== estado.salvo.json
            || campoCalendar.value !== estado.salvo.calendar
            || (checkEnabled && checkEnabled.checked !== estado.salvo.enabled);
        indicadorPendente.classList.toggle('is-visivel', !!sujo);
    }

    function testePleno(registro) {
        if (!registro) {
            return false;
        }
        var papel = registro.accessRole || '';
        return papel === '' || papel === 'owner' || papel === 'writer';
    }

    function atualizarStatusGeral() {
        if (!pill) {
            return;
        }
        pill.classList.remove('is-configurado', 'is-testado');
        if (testePleno(estado.ultimoTeste)) {
            pill.classList.add('is-testado');
            pill.textContent = 'Testado ✓';
        } else if (estado.configurado) {
            pill.classList.add('is-configurado');
            pill.textContent = 'Configurado — não testado';
        } else {
            pill.textContent = 'Não configurado';
        }
    }

    function duasCasas(n) {
        return String(n).padStart(2, '0');
    }

    function formatarQuando(mysql) {
        if (!mysql || typeof mysql !== 'string') {
            return '';
        }
        var partes = mysql.split(' ');
        var data = (partes[0] || '').split('-');
        var hora = (partes[1] || '').split(':');
        if (data.length !== 3) {
            return mysql;
        }
        return duasCasas(data[2]) + '/' + duasCasas(data[1]) + '/' + data[0] + ' às ' + duasCasas(hora[0] || '00') + ':' + duasCasas(hora[1] || '00');
    }

    function rotuloPermissao(papel) {
        var nomes = {
            owner: 'proprietário',
            writer: 'editor (cria e edita eventos)',
            reader: 'somente leitura',
            freeBusyReader: 'somente livre/ocupado'
        };
        return nomes[papel] || papel || '—';
    }

    function mostrarUltimo() {
        var registro = estado.ultimoTeste;
        if (!registro) {
            blocoUltimo.hidden = true;
            return;
        }
        var quando = formatarQuando(registro.quando);
        var resumo = registro.summary || registro.calendar_id || '';
        blocoUltimo.hidden = false;
        textoUltimo.textContent = testePleno(registro)
            ? 'Último teste bem-sucedido em ' + quando + ' — "' + resumo + '" (' + rotuloPermissao(registro.accessRole) + ').'
            : 'Último teste em ' + quando + ' conectou com permissão ' + rotuloPermissao(registro.accessRole) + ' — o envio de eventos exige permissão de edição.';
    }

    function limparPainel() {
        while (painel.firstChild) {
            painel.removeChild(painel.firstChild);
        }
    }

    function montarPainel(tipo, icone, titulo, mensagem) {
        limparPainel();
        painel.className = 'apreas-gcal-resultado is-' + tipo;
        painel.hidden = false;

        var ic = document.createElement('span');
        ic.className = 'dashicons dashicons-' + icone + (tipo === 'testando' ? ' apreas-gcal-girar' : '');
        ic.setAttribute('aria-hidden', 'true');

        var corpo = document.createElement('div');
        corpo.className = 'apreas-gcal-resultado-corpo';
        var t = document.createElement('strong');
        t.textContent = titulo;
        var p = document.createElement('p');
        p.textContent = mensagem;
        corpo.appendChild(t);
        corpo.appendChild(p);

        painel.appendChild(ic);
        painel.appendChild(corpo);
        return corpo;
    }

    function adicionarDica(corpo, titulo, texto) {
        var dica = document.createElement('div');
        dica.className = 'apreas-gcal-dica';
        var forte = document.createElement('strong');
        forte.textContent = titulo + ' ';
        dica.appendChild(forte);
        dica.appendChild(document.createTextNode(texto));
        corpo.appendChild(dica);
    }

    function adicionarDetalhes(corpo, pares) {
        var dl = document.createElement('dl');
        dl.className = 'apreas-gcal-detalhes';
        pares.forEach(function (par) {
            var dt = document.createElement('dt');
            dt.textContent = par[0];
            var dd = document.createElement('dd');
            dd.textContent = par[1];
            dl.appendChild(dt);
            dl.appendChild(dd);
        });
        corpo.appendChild(dl);
    }

    function guiaPorCodigo(codigo) {
        var guias = {
            sem_calendar_id: 'Preencha o Calendar ID no passo 2.',
            json_invalido: 'Volte ao passo 1 e cole o arquivo JSON completo da service account.',
            sem_credenciais: 'Volte ao passo 1 e cole o arquivo JSON completo da service account.',
            json_tipo: 'Crie uma chave de service account (IAM e administrador → Contas de serviço → Chaves → JSON) e cole no passo 1.',
            sem_openssl: 'A extensão OpenSSL do PHP não está disponível no servidor. Peça ao suporte da hospedagem para ativá-la.',
            jwt_falhou: 'A private_key parece corrompida. Baixe uma nova chave JSON no Google Cloud e cole no passo 1.',
            token_negado: 'O Google recusou a chave. Causas comuns: chave revogada ou relógio do servidor errado. Gere uma nova chave JSON (passo 1), confira a data/hora do servidor e teste de novo.',
            token_falhou: 'O servidor não conseguiu falar com o Google. Verifique se a hospedagem permite requisições HTTPS de saída (firewall/proxy).',
            conexao_falhou: 'O servidor não conseguiu falar com o Google. Verifique se a hospedagem permite requisições HTTPS de saída (firewall/proxy).',
            gcal_http_401: 'A autenticação foi recusada. Gere uma nova chave JSON no passo 1 e teste de novo.',
            gcal_http_403: 'Sem permissão: 1) ative a Google Calendar API no projeto da chave (APIs e serviços → Biblioteca); 2) compartilhe o calendário com a service account usando "Fazer alterações nos eventos".',
            gcal_http_404: 'Calendar ID não encontrado. Confira o ID no passo 2 (Integrar calendário → Calendar ID) e se o calendário foi compartilhado com o e-mail da service account.',
            acesso_negado: 'Sua sessão não tem permissão para esta seção. Recarregue a página e entre com um usuário administrador.'
        };
        return guias[codigo] || 'Confira os passos 1 e 2 e teste de novo. Se persistir, anote a mensagem acima.';
    }

    function pedido(action, extra) {
        var corpo = new URLSearchParams();
        corpo.set('action', action);
        corpo.set('nonce', cfg.nonce);
        Object.keys(extra || {}).forEach(function (chave) {
            corpo.set(chave, extra[chave]);
        });

        return fetch(cfg.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: corpo.toString()
        }).then(function (resposta) {
            return resposta.text().then(function (texto) {
                try {
                    return JSON.parse(texto);
                } catch (e) {
                    throw new Error('O WordPress respondeu algo inesperado (HTTP ' + resposta.status + '). Recarregue a página e tente de novo.');
                }
            });
        });
    }

    function limparTimers() {
        timersEstagio.forEach(function (t) {
            clearTimeout(t);
        });
        timersEstagio = [];
    }

    function fimTeste() {
        limparTimers();
        estado.testando = false;
        setIcone(iconeTestar, 'update', false);
        textoTestar.textContent = 'Testar conexão';
        btnSalvar.disabled = false;
        atualizarBotaoTestar();
    }

    function aoTestar() {
        if (estado.testando || !estado.json.ok || !estado.calendar.ok) {
            return;
        }
        estado.testando = true;
        btnTestar.disabled = true;
        btnSalvar.disabled = true;
        setIcone(iconeTestar, 'update', true);
        textoTestar.textContent = 'Testando…';
        atualizarPasso3();

        var corpo = montarPainel('testando', 'update', 'Testando conexão…', 'Autenticando com o Google…');
        var mensagem = corpo.querySelector('p');
        timersEstagio.push(setTimeout(function () {
            mensagem.textContent = 'Lendo o calendário…';
        }, 1200));

        pedido('apreas_gcal_testar', {
            json: campoJson.value,
            calendar_id: campoCalendar.value.trim()
        }).then(function (resposta) {
            if (resposta && resposta.success) {
                var d = resposta.data || {};
                estado.ultimoTeste = d.ultimoTeste || null;
                var papel = d.accessRole || '';

                if (papel === 'reader' || papel === 'freeBusyReader') {
                    estado.passo3 = 'aviso';
                    var corpoAviso = montarPainel('aviso', 'warning', 'Conectou, mas a permissão é somente leitura', 'O Google aceitou a chave, porém a service account tem acesso "' + rotuloPermissao(papel) + '" a este calendário. O envio dos eventos vai falhar.');
                    adicionarDica(corpoAviso, 'Como resolver:', 'no Google Calendar, vá em Configurações → seu calendário → Compartilhar com pessoas específicas, troque a permissão da service account para "Fazer alterações nos eventos" e teste de novo.');
                } else {
                    estado.passo3 = 'done';
                    var corpoOk = montarPainel('ok', 'yes-alt', 'Conexão bem-sucedida', 'O Google autenticou a chave e leu o calendário. Nenhum evento foi criado.');
                    adicionarDetalhes(corpoOk, [
                        ['Calendário', d.summary || '—'],
                        ['Calendar ID', d.id || campoCalendar.value.trim()],
                        ['Fuso horário', d.timeZone || '—'],
                        ['Permissão', rotuloPermissao(papel)]
                    ]);
                    var sujo = campoJson.value !== estado.salvo.json || campoCalendar.value !== estado.salvo.calendar;
                    adicionarDica(corpoOk, 'Próximo passo:', sujo
                        ? 'clique em "Salvar configurações" para gravar a chave e o Calendar ID. Depois disso, todo Evento publicado será enviado automaticamente.'
                        : 'a configuração já está salva. Todo Evento publicado será enviado automaticamente para este calendário.');
                }
                mostrarUltimo();
                atualizarStatusGeral();
            } else {
                var erro = (resposta && resposta.data) || {};
                estado.passo3 = 'error';
                var corpoErro = montarPainel('erro', 'dismiss', 'O teste falhou', erro.mensagem || 'O Google não aceitou a conexão.');
                adicionarDica(corpoErro, 'Como resolver:', guiaPorCodigo(erro.codigo));
            }
            fimTeste();
        }).catch(function (err) {
            estado.passo3 = 'error';
            var corpoErro = montarPainel('erro', 'dismiss', 'Falha de comunicação', (err && err.message) ? err.message : 'Não foi possível falar com o WordPress (admin-ajax). Verifique sua conexão e tente de novo.');
            adicionarDica(corpoErro, 'Como resolver:', 'recarregue a página, confira se a sessão continua ativa e teste novamente.');
            fimTeste();
        });
    }

    function fimSalvar() {
        estado.salvando = false;
        setIcone(iconeSalvar, 'saved', false);
        textoSalvar.textContent = 'Salvar configurações';
        btnSalvar.disabled = false;
    }

    function aoSalvar() {
        if (estado.salvando) {
            return;
        }
        estado.salvando = true;
        btnSalvar.disabled = true;
        setIcone(iconeSalvar, 'update', true);
        textoSalvar.textContent = 'Salvando…';

        pedido('apreas_gcal_salvar', {
            json: campoJson.value,
            calendar_id: campoCalendar.value.trim(),
            enabled: (checkEnabled && checkEnabled.checked) ? '1' : '0'
        }).then(function (resposta) {
            if (resposta && resposta.success) {
                var d = resposta.data || {};
                estado.salvo = {
                    json: campoJson.value,
                    calendar: campoCalendar.value,
                    enabled: checkEnabled ? checkEnabled.checked : true
                };
                estado.configurado = !!d.configurado;
                estado.ultimoTeste = d.ultimoTeste || null;
                if (!estado.ultimoTeste) {
                    estado.passo3 = 'pending';
                }

                var quando = formatarQuando(d.quando);
                var corpoOk = montarPainel('ok', 'saved', 'Configurações salvas', 'Gravado em ' + quando + ', sem sair da página.' + (estado.configurado ? ' Chave e Calendar ID armazenados.' : ' Ainda falta preencher a chave ou o Calendar ID.'));
                if (estado.configurado && !testePleno(estado.ultimoTeste)) {
                    adicionarDica(corpoOk, 'Próximo passo:', 'clique em "Testar conexão" para validar a chave e o compartilhamento do calendário.');
                }
                atualizarPendente();
                atualizarStatusGeral();
                mostrarUltimo();
                atualizarPasso3();
            } else {
                var erro = (resposta && resposta.data) || {};
                var corpoErro = montarPainel('erro', 'dismiss', 'Não foi possível salvar', erro.mensagem || 'Erro desconhecido ao salvar.');
                if (erro.codigo === 'json_invalido') {
                    adicionarDica(corpoErro, 'Como resolver:', 'volte ao passo 1 e cole o arquivo JSON completo da service account. Nada foi alterado no banco.');
                }
            }
            fimSalvar();
        }).catch(function (err) {
            var corpoErro = montarPainel('erro', 'dismiss', 'Falha de comunicação', (err && err.message) ? err.message : 'Não foi possível falar com o WordPress (admin-ajax). Nada foi salvo.');
            adicionarDica(corpoErro, 'Como resolver:', 'recarregue a página e tente salvar novamente.');
            fimSalvar();
        });
    }

    function copiarEmail(botao) {
        if (!estado.json.dados) {
            return;
        }
        var texto = estado.json.dados.client_email;
        var rotulo = botao.querySelector('[data-copiar-texto]');
        var original = rotulo.textContent;

        function sucesso() {
            botao.classList.add('is-copiado');
            rotulo.textContent = 'Copiado!';
            setTimeout(function () {
                botao.classList.remove('is-copiado');
                rotulo.textContent = original;
            }, 1600);
        }

        function fallback() {
            var area = document.createElement('textarea');
            area.value = texto;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();
            try {
                document.execCommand('copy');
                sucesso();
            } catch (e) {
                rotulo.textContent = 'Copie manualmente';
                setTimeout(function () {
                    rotulo.textContent = original;
                }, 1600);
            }
            document.body.removeChild(area);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(texto).then(sucesso).catch(fallback);
        } else {
            fallback();
        }
    }

    campoJson.addEventListener('input', atualizarPasso1);
    campoCalendar.addEventListener('input', atualizarPasso2);
    if (checkEnabled) {
        checkEnabled.addEventListener('change', atualizarPendente);
    }
    btnTestar.addEventListener('click', aoTestar);
    btnSalvar.addEventListener('click', aoSalvar);

    raiz.querySelector('[data-usar-email]').addEventListener('click', function () {
        campoCalendar.value = cfg.userEmail || '';
        atualizarPasso2();
        campoCalendar.focus();
    });

    raiz.querySelectorAll('[data-copiar]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            copiarEmail(botao);
        });
    });

    raiz.querySelectorAll('[data-ir]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var n = botao.getAttribute('data-ir');
            var secao = raiz.querySelector('[data-passo="' + n + '"]');
            secao.scrollIntoView({ behavior: 'smooth', block: 'center' });
            var alvo = n === '1' ? campoJson : (n === '2' ? campoCalendar : btnTestar);
            setTimeout(function () {
                alvo.focus({ preventScroll: true });
            }, 250);
        });
    });

    raiz.querySelectorAll('[data-passo]').forEach(function (secao) {
        secao.addEventListener('focusin', function () {
            estado.foco = parseInt(secao.getAttribute('data-passo'), 10);
            if (estado.foco === 1 && !estado.json.ok) {
                atualizarPasso1();
            } else if (estado.foco === 2 && !estado.calendar.ok) {
                atualizarPasso2();
            }
        });
    });

    atualizarPasso1();
    atualizarPasso2();
    atualizarStatusGeral();
    mostrarUltimo();
    atualizarPendente();
})();