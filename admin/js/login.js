jQuery(document).ready(function ($) {

    // LIMPAR ESPAÇOS EM BRANCO DO INPUT SENHA_NOME
    let timeout;
    var elSenhaNome = document.getElementById("senha_nome");
    if (elSenhaNome) {
        elSenhaNome.addEventListener("input", function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.value = this.value.trim();
            }, 4000);
        });
    }
    // LIMPAR ESPAÇOS EM BRANCO DO INPUT SENHA_NOME

    // MÁSCARA DD/MM/AAAA PARA CAMPO DATA
    function aplicarMascaraData(input) {
        input.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '').substring(0, 8);
            if (v.length >= 5) {
                v = v.substring(0, 2) + '/' + v.substring(2, 4) + '/' + v.substring(4);
            } else if (v.length >= 3) {
                v = v.substring(0, 2) + '/' + v.substring(2);
            }
            this.value = v;
        });
    }
    document.querySelectorAll('#data_nascimento').forEach(function (el) {
        aplicarMascaraData(el);
    });
    // MÁSCARA DD/MM/AAAA PARA CAMPO DATA

    // CONVERTE DD/MM/AAAA → YYYY-MM-DD
    function converterData(dataBR) {
        var partes = dataBR.split('/');
        if (partes.length === 3 && partes[2].length === 4) {
            return partes[2] + '-' + partes[1] + '-' + partes[0];
        }
        return dataBR;
    }
    // CONVERTE DD/MM/AAAA → YYYY-MM-DD

    // HELPERS PARA SHORTCODES REPETÍVEIS (preenchem TODAS as instâncias da página)
    function setAllText(sel, val) {
        document.querySelectorAll(sel).forEach(function (el) {
            el.textContent = val || '';
        });
    }
    function setAllVal(sel, val) {
        document.querySelectorAll(sel).forEach(function (el) {
            el.value = val || '';
        });
    }
    // HELPERS PARA SHORTCODES REPETÍVEIS

    // ============================================================
    // PERSÍSTÊNCIA DE SESSÃO (localStorage - expira em 10 minutos)
    // ============================================================
    var SESSAO_KEY = 'apreas_login_sessao';
    var SESSAO_TTL = 10 * 60 * 1000; // 10 minutos em ms

    function salvarSessao(tipo, dados) {
        try {
            var sessao = { tipo: tipo, dados: dados, expira: Date.now() + SESSAO_TTL };
            localStorage.setItem(SESSAO_KEY, JSON.stringify(sessao));
        } catch (e) { console.warn('Sessao nao salva:', e); }
    }

    function lerSessao() {
        try {
            var raw = localStorage.getItem(SESSAO_KEY);
            if (!raw) return null;
            var s = JSON.parse(raw);
            if (Date.now() > s.expira) {
                localStorage.removeItem(SESSAO_KEY);
                return null;
            }
            return s;
        } catch (e) {
            localStorage.removeItem(SESSAO_KEY);
            return null;
        }
    }

    function atualizarDadosAluno(dados, tipo) {
        if (!dados) return;

        var d = (tipo === 'eventos' && Array.isArray(dados) && dados.length > 0) ? dados[0] : dados;

        document.querySelectorAll('.dados-aluno-container').forEach(function (container) {
            container.style.display = 'block';
        });
        setAllText('.aluno-nome', d.nome || '');
        if (d.escola) setAllText('.aluno-escola', d.escola.nome || '');
        if (d.turma) setAllText('.aluno-turma', d.turma.nome || '');
        if (d.unidade) setAllText('.aluno-unidade', d.unidade.nome || '');

        if (d.data_nascimento) {
            var dn = d.data_nascimento;
            if (dn.includes('-')) {
                var p = dn.split('-');
                if (p.length === 3) dn = p[2] + '/' + p[1] + '/' + p[0];
            }
            setAllText('.aluno-data-nascimento', dn);
        }
    }

    function aplicarSessaoEscola(data) {
        atualizarDadosAluno(data, 'escola');
        if (data.escola) {
            if (data.escola.imagem_logo_escola) {
                $('.imagem_logo_escola').attr('src', data.escola.imagem_logo_escola).attr('srcset', data.escola.imagem_logo_escola);
            }
            setAllText('.l1_escolha_data_inicio_escola', data.escola.l1_escolha_data_inicio || '');
            setAllText('.l1_escolha_data_fim_escola', data.escola.l1_escolha_data_fim || '');
            setAllText('.l1_entrega_data_escola', data.escola.l1_entrega_data || '');
            setAllText('.l1_entrega_data_inicio_escola', data.escola.l1_entrega_data_inicio || '');
            setAllText('.l1_entrega_data_fim_escola', data.escola.l1_entrega_data_fim || '');
            setAllText('.l2_escolha_data_inicio_escola', data.escola.l2_escolha_data_inicio || '');
            setAllText('.l2_escolha_data_fim_escola', data.escola.l2_escolha_data_fim || '');
            setAllText('.l2_entrega_data_escola', data.escola.l2_entrega_data || '');
            setAllText('.l2_entrega_data_inicio_escola', data.escola.l2_entrega_data_inicio || '');
            setAllText('.l2_entrega_data_fim_escola', data.escola.l2_entrega_data_fim || '');
            setAllText('.l1_status_escola', data.escola.l1_status || 'FINALIZADO');
            setAllText('.l2_status_escola', data.escola.l2_status || 'FINALIZADO');
            setAllText('.data_limite_fotos_escola', data.escola.data_limite_fotos || '');
        }
        if (data.imagem_upload_individual) {
            $('.imagem_upload_individual img').attr('src', data.imagem_upload_individual).attr('srcset', data.imagem_upload_individual);
        }
        if (data.imagem_upload_individual2) {
            $('.imagem_upload_individual2 img').attr('src', data.imagem_upload_individual2).attr('srcset', data.imagem_upload_individual2);
        }
        if (data.imagem_upload_turma) {
            $('.imagem_upload_turma img').attr('src', data.imagem_upload_turma).attr('srcset', data.imagem_upload_turma);
        }
        if (data.nome) setAllVal('.nome input', data.nome);
        if (data.escola) setAllVal('.escola input', data.escola.nome || '');
        if (data.unidade) setAllVal('.unidade input', data.unidade.nome || '');
        if (data.turma) setAllVal('.turma input', data.turma.nome || '');
        var lc = document.getElementById('loginContainer');
        if (lc) lc.classList.add('d-none');
    }

    function aplicarSessaoEventos(dataArray) {
        atualizarDadosAluno(dataArray, 'eventos');
        if (!dataArray || !dataArray.length) return;
        var d = dataArray[0];
        if (d.imagem_upload_individual) {
            $('.imagem_upload_individual').find('img').attr('src', d.imagem_upload_individual).attr('srcset', d.imagem_upload_individual);
        }
        if (d.imagem_upload_individual2) {
            $('.imagem_upload_individual2').find('img').attr('src', d.imagem_upload_individual2).attr('srcset', d.imagem_upload_individual2);
        }
        if (d.imagem_upload_turma) {
            $('.imagem_upload_turma').find('img').attr('src', d.imagem_upload_turma).attr('srcset', d.imagem_upload_turma);
        }
        if (d.link_album) {
            $('.apreas-link-album').attr('href', d.link_album).show();
        }
        if (dataArray.length > 0 && d.fotos_participante && d.fotos_participante.length > 0) {
            var fotosHtml = '<div class="row">';
            $.each(d.fotos_participante, function (index, foto) {
                fotosHtml += '<div class="col-6 col-md-3 col-lg-2 mb-4"><div class="mb-3 fotos-para-selecionar" style="position: relative; text-align: center;"><img src="' + foto.caminho + '" alt="' + foto.nome + '" class="img-fluid mb-4"><button type="button" class="btn btn-primary select-photo text-white" data-codigo="' + foto.codigo + '">Selecionar</button></div></div>';
            });
            fotosHtml += '</div>';
            document.querySelectorAll('.apreas-fotos-container').forEach(function (container) {
                container.innerHTML = fotosHtml;
            });
            updateCheckboxListener();
        }
        if (d.nome) setAllVal('.nomeoculto input', d.nome);
        if (d.escola && d.evento) setAllVal('.escolaevento input', (d.escola.nome || '') + ' / ' + (d.evento.nome || ''));
        if (d.evento) setAllVal('.evento input', d.evento.nome || '');
        if (d.escola) setAllVal('.escola input', d.escola.nome || '');
        if (d.unidade) setAllVal('.unidade input', d.unidade.nome || '');
        if (d.turma) setAllVal('.turma input', d.turma.nome || '');
        if (d.evento) {
            if (d.evento.imagem_logo_evento) {
                $('.imagem_logo_evento').attr('src', d.evento.imagem_logo_evento).attr('srcset', d.evento.imagem_logo_evento);
            }
            setAllText('.l1_escolha_data_inicio_evento', d.evento.l1_escolha_data_inicio || '');
            setAllText('.l1_escolha_data_fim_evento', d.evento.l1_escolha_data_fim || '');
            setAllText('.l1_entrega_data_evento', d.evento.l1_entrega_data || '');
            setAllText('.l1_entrega_data_inicio_evento', d.evento.l1_entrega_data_inicio || '');
            setAllText('.l1_entrega_data_fim_evento', d.evento.l1_entrega_data_fim || '');
            setAllText('.l2_escolha_data_inicio_evento', d.evento.l2_escolha_data_inicio || '');
            setAllText('.l2_escolha_data_fim_evento', d.evento.l2_escolha_data_fim || '');
            setAllText('.l2_entrega_data_evento', d.evento.l2_entrega_data || '');
            setAllText('.l2_entrega_data_inicio_evento', d.evento.l2_entrega_data_inicio || '');
            setAllText('.l2_entrega_data_fim_evento', d.evento.l2_entrega_data_fim || '');
            setAllText('.l1_status_evento', d.evento.l1_status || 'FINALIZADO');
            setAllText('.l2_status_evento', d.evento.l2_status || 'FINALIZADO');
        }
        if (d.escola) {
            if (d.escola.imagem_logo_escola) {
                $('.imagem_logo_escola').attr('src', d.escola.imagem_logo_escola).attr('srcset', d.escola.imagem_logo_escola);
            }
            setAllText('.l1_escolha_data_inicio_escola', d.escola.l1_escolha_data_inicio || '');
            setAllText('.l1_escolha_data_fim_escola', d.escola.l1_escolha_data_fim || '');
            setAllText('.l1_entrega_data_escola', d.escola.l1_entrega_data || '');
            setAllText('.l1_entrega_data_inicio_escola', d.escola.l1_entrega_data_inicio || '');
            setAllText('.l1_entrega_data_fim_escola', d.escola.l1_entrega_data_fim || '');
            setAllText('.l2_escolha_data_inicio_escola', d.escola.l2_escolha_data_inicio || '');
            setAllText('.l2_escolha_data_fim_escola', d.escola.l2_escolha_data_fim || '');
            setAllText('.l2_entrega_data_escola', d.escola.l2_entrega_data || '');
            setAllText('.l2_entrega_data_inicio_escola', d.escola.l2_entrega_data_inicio || '');
            setAllText('.l2_entrega_data_fim_escola', d.escola.l2_entrega_data_fim || '');
            setAllText('.l1_status_escola', d.escola.l1_status || 'FINALIZADO');
            setAllText('.l2_status_escola', d.escola.l2_status || 'FINALIZADO');
        }
        var lc = document.getElementById('loginContainer');
        if (lc) lc.classList.add('d-none');
    }
    // ============================================================

    // MODAL FOTO HMTL
    document.body.insertAdjacentHTML('beforeend', `
        <div id="customModal" class="custom-modal" style="display: none;">
            <div class="custom-modal-content">
                <span id="closeCustomModal" class="custom-close">&times;</span>
                <img id="modalImage" src="" alt="Imagem do Participante" class="custom-modal-image">
            </div>
        </div>
    `);
    // MODAL FOTO HMTL

    $('#form_login').submit(function (e) {
        e.preventDefault();
        var formDataArray = $(this).serializeArray();
        var formData = {};
        $.each(formDataArray, function () {
            formData[this.name] = this.value;
        });
        // Converter data de DD/MM/AAAA para YYYY-MM-DD antes do envio
        if (formData['data_nascimento']) {
            formData['data_nascimento'] = converterData(formData['data_nascimento']);
        }
        $.ajax({
            type: 'POST',
            url: location.origin + '/wp-admin/admin-ajax.php',
            data: {
                action: 'process_login_form',
                formData: formData
            },
            dataType: 'json',
            success: function (response) {
                if (response.success == true) {

                    // CAMPOS EXTRAS | ESCOLAS
                    //  CAMPOS EXTRAS | ESCOLAS

                    if (response.data.escola) {
                        if (response.data.escola.imagem_logo_escola) {
                            $('.imagem_logo_escola').attr('src', response.data.escola.imagem_logo_escola);
                            $('.imagem_logo_escola').attr('srcset', response.data.escola.imagem_logo_escola);
                        }
                        setAllText('.l1_escolha_data_inicio_escola', response.data.escola.l1_escolha_data_inicio || '');
                        setAllText('.l1_escolha_data_fim_escola', response.data.escola.l1_escolha_data_fim || '');
                        setAllText('.l1_entrega_data_escola', response.data.escola.l1_entrega_data || '');
                        setAllText('.l1_entrega_data_inicio_escola', response.data.escola.l1_entrega_data_inicio || '');
                        setAllText('.l1_entrega_data_fim_escola', response.data.escola.l1_entrega_data_fim || '');
                        setAllText('.l2_escolha_data_inicio_escola', response.data.escola.l2_escolha_data_inicio || '');
                        setAllText('.l2_escolha_data_fim_escola', response.data.escola.l2_escolha_data_fim || '');
                        setAllText('.l2_entrega_data_escola', response.data.escola.l2_entrega_data || '');
                        setAllText('.l2_entrega_data_inicio_escola', response.data.escola.l2_entrega_data_inicio || '');
                        setAllText('.l2_entrega_data_fim_escola', response.data.escola.l2_entrega_data_fim || '');
                        setAllText('.l1_status_escola', response.data.escola.l1_status || 'FINALIZADO');
                        setAllText('.l2_status_escola', response.data.escola.l2_status || 'FINALIZADO');
                        setAllText('.data_limite_fotos_escola', response.data.escola.data_limite_fotos || '');
                    }

                    //console.log(response.data);
                    salvarSessao('escola', response.data);
                    aplicarSessaoEscola(response.data);

                    $(document.body).trigger('apreas_login_success');

                    // IMAGEM
                    if (response.data.imagem_upload_individual) {
                        $('.imagem_upload_individual img').attr('src', response.data.imagem_upload_individual);
                    }
                    if (response.data.imagem_upload_individual) {
                        $('.imagem_upload_individual img').attr('srcset', response.data.imagem_upload_individual);
                    }

                    if (response.data.imagem_upload_individual2) {
                        $('.imagem_upload_individual2 img').attr('src', response.data.imagem_upload_individual2);
                    }
                    if (response.data.imagem_upload_individual2) {
                        $('.imagem_upload_individual2 img').attr('srcset', response.data.imagem_upload_individual2);
                    }

                    if (response.data.imagem_upload_turma) {
                        $('.imagem_upload_turma img').attr('src', response.data.imagem_upload_turma);
                    }
                    if (response.data.imagem_upload_turma) {
                        $('.imagem_upload_turma img').attr('srcset', response.data.imagem_upload_turma);
                    }
                    // IMAGEM

                    var loginContainer = document.getElementById('loginContainer');
                    if (loginContainer) {
                        loginContainer.classList.add('d-none');
                    }
                    var logoutContainer = document.getElementById('logoutContainer');
                    if (logoutContainer) {
                        logoutContainer.style.setProperty('display', 'flex', 'important');
                    }
                    window.scrollTo(0, 0);
                }
                if (response.success == false) {
                    console.log(response.data);
                    Swal.fire({
                        title: 'Aluno não encontrado.',
                        text: '',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'custom-confirm-button'
                        }
                    });
                }

                // ENVIAR VALORES PARA O FORMULÁRIO

                if (response.data.nome) setAllVal('.nome input', response.data.nome);
                if (response.data.evento) setAllVal('.evento input', response.data.evento.nome || '');
                if (response.data.escola) setAllVal('.escola input', response.data.escola.nome || '');
                if (response.data.unidade) setAllVal('.unidade input', response.data.unidade.nome || '');
                if (response.data.turma) setAllVal('.turma input', response.data.turma.nome || '');
                // ENVIAR VALORES PARA O FORMULÁRIO

            },
            error: function (response) {
                console.log(response.data);
                Swal.fire({
                    title: 'Aluno não encontrado.',
                    text: '',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    $('#form_login_eventos').submit(function (e) {
        e.preventDefault();
        var formDataArray = $(this).serializeArray();
        var formData = {};
        $.each(formDataArray, function () {
            formData[this.name] = this.value;
        });
        // Converter data de DD/MM/AAAA para YYYY-MM-DD antes do envio
        if (formData['data_nascimento']) {
            formData['data_nascimento'] = converterData(formData['data_nascimento']);
        }

        $.ajax({
            type: 'POST',
            url: location.origin + '/wp-admin/admin-ajax.php',
            data: {
                action: 'process_login_form_eventos',
                formData: formData
            },
            dataType: 'json',
            success: function (response) {
                //console.log(response)
                if (response.success == true) {
                    // IMAGEM
                    if (response.data[0].imagem_upload_individual) {
                        $('.imagem_upload_individual').find('img').attr('src', response.data[0].imagem_upload_individual);
                        $('.imagem_upload_individual').find('img').attr('srcset', response.data[0].imagem_upload_individual);
                    }
                    if (response.data[0].imagem_upload_individual2) {
                        $('.imagem_upload_individual2').find('img').attr('src', response.data[0].imagem_upload_individual2);
                        $('.imagem_upload_individual2').find('img').attr('srcset', response.data[0].imagem_upload_individual2);
                    }
                    if (response.data[0].imagem_upload_turma) {
                        $('.imagem_upload_turma').find('img').attr('src', response.data[0].imagem_upload_turma);
                        $('.imagem_upload_turma').find('img').attr('srcset', response.data[0].imagem_upload_turma);
                    }
                    // IMAGEM

                    // LINK ALBUM
                    if (response.data[0].link_album) {
                        $('.apreas-link-album').attr('href', response.data[0].link_album).show();
                    }
                    // LINK ALBUM


                    // FOTOS PARTICIPANTE
                    if (response.data.length > 0 && response.data[0].fotos_participante) {
                        var fotosHtml = '<div class="row">';
                        $.each(response.data[0].fotos_participante, function (index, foto) {
                            fotosHtml += `
                            <div class="col-6 col-md-3 col-lg-2 mb-4">
                                <div class="mb-3 fotos-para-selecionar" style="position: relative; text-align: center;">
                                    <img src="${foto.caminho}" alt="${foto.nome}" class="img-fluid mb-4">
                                    <button type="button" class="btn btn-primary select-photo text-white" data-codigo="${foto.codigo}">
                                        Selecionar
                                    </button>
                                </div>
                            </div>
                            `;
                        });
                        fotosHtml += '</div>';
                        document.querySelectorAll('.apreas-fotos-container').forEach(function (container) {
                            container.innerHTML = fotosHtml;
                        });
                        updateCheckboxListener();

                        // ENVIAR VALORES PARA O FORMULÁRIO
                        if (response.data[0].nome) setAllVal('.nomeoculto input', response.data[0].nome);
                        if (response.data[0].escola && response.data[0].evento) {
                            setAllVal('.escolaevento input', `${response.data[0].escola.nome || ''} / ${response.data[0].evento.nome || ''}`);
                        }
                        if (response.data[0].evento) setAllVal('.evento input', response.data[0].evento.nome || '');
                        if (response.data[0].escola) setAllVal('.escola input', response.data[0].escola.nome || '');
                        if (response.data[0].unidade) setAllVal('.unidade input', response.data[0].unidade.nome || '');
                        if (response.data[0].turma) setAllVal('.turma input', response.data[0].turma.nome || '');
                        // ENVIAR VALORES PARA O FORMULÁRIO

                    }
                    // FOTOS PARTICIPANTE

                    // CAMPOS EXTRAS | EVENTOS
                    //  CAMPOS EXTRAS | EVENTOS

                    // CAMPOS EXTRAS | ESCOLAS
                    //  CAMPOS EXTRAS | ESCOLAS

                    if (response.data[0].evento) {
                        if (response.data[0].evento.imagem_logo_evento) {
                            $('.imagem_logo_evento').attr('src', response.data[0].evento.imagem_logo_evento);
                            $('.imagem_logo_evento').attr('srcset', response.data[0].evento.imagem_logo_evento);
                        }
                        setAllText('.l1_escolha_data_inicio_evento', response.data[0].evento.l1_escolha_data_inicio || '');
                        setAllText('.l1_escolha_data_fim_evento', response.data[0].evento.l1_escolha_data_fim || '');
                        setAllText('.l1_entrega_data_evento', response.data[0].evento.l1_entrega_data || '');
                        setAllText('.l1_entrega_data_inicio_evento', response.data[0].evento.l1_entrega_data_inicio || '');
                        setAllText('.l1_entrega_data_fim_evento', response.data[0].evento.l1_entrega_data_fim || '');
                        setAllText('.l2_escolha_data_inicio_evento', response.data[0].evento.l2_escolha_data_inicio || '');
                        setAllText('.l2_escolha_data_fim_evento', response.data[0].evento.l2_escolha_data_fim || '');
                        setAllText('.l2_entrega_data_evento', response.data[0].evento.l2_entrega_data || '');
                        setAllText('.l2_entrega_data_inicio_evento', response.data[0].evento.l2_entrega_data_inicio || '');
                        setAllText('.l2_entrega_data_fim_evento', response.data[0].evento.l2_entrega_data_fim || '');
                        setAllText('.l1_status_evento', response.data[0].evento.l1_status || 'FINALIZADO');
                        setAllText('.l2_status_evento', response.data[0].evento.l2_status || 'FINALIZADO');
                    }

                    if (response.data[0].escola) {
                        if (response.data[0].escola.imagem_logo_escola) {
                            $('.imagem_logo_escola').attr('src', response.data[0].escola.imagem_logo_escola);
                            $('.imagem_logo_escola').attr('srcset', response.data[0].escola.imagem_logo_escola);
                        }
                        setAllText('.l1_escolha_data_inicio_escola', response.data[0].escola.l1_escolha_data_inicio || '');
                        setAllText('.l1_escolha_data_fim_escola', response.data[0].escola.l1_escolha_data_fim || '');
                        setAllText('.l1_entrega_data_escola', response.data[0].escola.l1_entrega_data || '');
                        setAllText('.l1_entrega_data_inicio_escola', response.data[0].escola.l1_entrega_data_inicio || '');
                        setAllText('.l1_entrega_data_fim_escola', response.data[0].escola.l1_entrega_data_fim || '');
                        setAllText('.l2_escolha_data_inicio_escola', response.data[0].escola.l2_escolha_data_inicio || '');
                        setAllText('.l2_escolha_data_fim_escola', response.data[0].escola.l2_escolha_data_fim || '');
                        setAllText('.l2_entrega_data_escola', response.data[0].escola.l2_entrega_data || '');
                        setAllText('.l2_entrega_data_inicio_escola', response.data[0].escola.l2_entrega_data_inicio || '');
                        setAllText('.l2_entrega_data_fim_escola', response.data[0].escola.l2_entrega_data_fim || '');
                        setAllText('.l1_status_escola', response.data[0].escola.l1_status || 'FINALIZADO');
                        setAllText('.l2_status_escola', response.data[0].escola.l2_status || 'FINALIZADO');
                    }

                    var loginContainer = document.getElementById('loginContainer');
                    if (loginContainer) {
                        loginContainer.classList.add('d-none');
                    }
                    var logoutContainer = document.getElementById('logoutContainer');
                    if (logoutContainer) {
                        logoutContainer.style.setProperty('display', 'flex', 'important');
                    }
                    salvarSessao('eventos', response.data);
                    aplicarSessaoEventos(response.data);

                    $(document.body).trigger('apreas_login_success');

                    window.scrollTo(0, 0);
                } else {
                    Swal.fire({
                        title: 'Participante não encontrado.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function (response) {
                Swal.fire({
                    title: 'Erro na solicitação.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // FUNÇÃO QUE COLOCA OS VALORES DAS FOTOS NO INPUT DO FORMULÁRIO
    function updateCheckboxListener() {
        function getSelectedValues() {
            // Captura todos os inputs de fotos selecionadas
            let selectedValues = [];
            const selectedInputs = document.querySelectorAll('input[name="selected_photos[]"]');
            selectedInputs.forEach(function (input) {
                selectedValues.push(input.value);
            });
            return selectedValues.join(',');
        }

        // Atualiza o contador de fotos selecionadas
        function updateSelectedCount() {
            const selectedInputs = document.querySelectorAll('input[name="selected_photos[]"]');
            const count = selectedInputs.length; // Conta os inputs de fotos selecionadas
            document.querySelectorAll('.apreas-selected-count').forEach(function (el) {
                el.textContent = count; // Atualiza o texto do contador
            });
        }

        // Atualiza o input com os valores selecionados
        function updateInputEscolha() {
            const selected = getSelectedValues();
            setAllVal('.escolha input', selected);
            updateSelectedCount(); // Atualiza o contador sempre que o input for alterado
        }

        document.querySelectorAll('.select-photo').forEach(function (button) {
            button.addEventListener('click', function () {
                const codigo = this.dataset.codigo;
                const existingInput = document.querySelector(`input[name="selected_photos[]"][value="${codigo}"]`);
                if (existingInput) {
                    existingInput.remove();
                    this.textContent = 'Selecionar';
                    this.classList.remove('btn-danger');
                    this.classList.add('btn-primary');
                    this.style.backgroundColor = '#C0FF2D';
                    this.style.color = '#000000';
                    this.style.borderColor = '#C0FF2D';
                } else {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_photos[]';
                    input.value = codigo;
                    const container = this.closest('.apreas-fotos-container');
                    if (container) container.appendChild(input);
                    this.textContent = 'Remover';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-danger');
                    this.style.backgroundColor = '#dc3545';
                    this.style.color = '#ffffff';
                    this.style.borderColor = '#dc3545';
                }
                updateInputEscolha(); // Atualiza o input e o contador
            });
        });

        // Modal Foto
        $('.img-fluid').click(function () {
            const imgSrc = $(this).attr('src');
            $('#modalImage').attr('src', imgSrc);
            $('#customModal').fadeIn();
        });

        $('#closeCustomModal').click(function () {
            $('#customModal').fadeOut();
        });

        $(window).click(function (event) {
            if (event.target === document.getElementById('customModal')) {
                $('#customModal').fadeOut();
            }
        });

        updateSelectedCount(); // Inicializa o contador
    }

    updateCheckboxListener();
    // FUNÇÃO QUE COLOCA OS VALORES DAS FOTOS NO INPUT DO FORMULÁRIO

    // RESTAURAR SESSÃO AO CARREGAR A PÁGINA
    var sessaoAtiva = lerSessao();
    if (sessaoAtiva) {
        if (sessaoAtiva.tipo === 'escola') {
            aplicarSessaoEscola(sessaoAtiva.dados);
        } else if (sessaoAtiva.tipo === 'eventos') {
            aplicarSessaoEventos(sessaoAtiva.dados);
        }
    }
    // RESTAURAR SESSÃO AO CARREGAR A PÁGINA

    // SAIR / LOGOUT
    $(document).on('click', '#btnSairApreas', function (e) {
        e.preventDefault();
        localStorage.removeItem(SESSAO_KEY);
        $(document).trigger('apreas_logout');
        location.reload();
    });
    // SAIR / LOGOUT

});
