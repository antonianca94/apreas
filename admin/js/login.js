jQuery(document).ready(function ($) {

    // LIMPAR ESPAÇOS EM BRANCO DO INPUT SENHA_NOME
    let timeout;
    document.getElementById("senha_nome").addEventListener("input", function () {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            this.value = this.value.trim();
        }, 4000);
    });
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
        var container = document.querySelector('.dados-aluno-container');
        if (!container) return;

        container.style.display = 'block';
        var elNome = document.querySelector('.aluno-nome');
        var elEscola = document.querySelector('.aluno-escola');
        var elTurma = document.querySelector('.aluno-turma');
        var elUnidade = document.querySelector('.aluno-unidade');
        var elDataNasc = document.querySelector('.aluno-data-nascimento');

        if (elNome) elNome.textContent = d.nome || '';
        if (elEscola && d.escola) elEscola.textContent = d.escola.nome || '';
        if (elTurma && d.turma) elTurma.textContent = d.turma.nome || '';
        if (elUnidade && d.unidade) elUnidade.textContent = d.unidade.nome || '';

        if (elDataNasc && d.data_nascimento) {
            var dn = d.data_nascimento;
            if (dn.includes('-')) {
                var p = dn.split('-');
                if (p.length === 3) dn = p[2] + '/' + p[1] + '/' + p[0];
            }
            elDataNasc.textContent = dn;
        }
    }

    function aplicarSessaoEscola(data) {
        atualizarDadosAluno(data, 'escola');
        var l1i = document.querySelector('.l1_escolha_data_inicio_escola');
        var l1f = document.querySelector('.l1_escolha_data_fim_escola');
        var l1e = document.querySelector('.l1_entrega_data_escola');
        var l2i = document.querySelector('.l2_escolha_data_inicio_escola');
        var l2f = document.querySelector('.l2_escolha_data_fim_escola');
        var l2e = document.querySelector('.l2_entrega_data_escola');
        if (data.escola) {
            if (data.escola.imagem_logo_escola) {
                $('.imagem_logo_escola').attr('src', data.escola.imagem_logo_escola).attr('srcset', data.escola.imagem_logo_escola);
            }
            if (l1i) l1i.textContent = data.escola.l1_escolha_data_inicio || '';
            if (l1f) l1f.textContent = data.escola.l1_escolha_data_fim || '';
            if (l1e) l1e.textContent = data.escola.l1_entrega_data || '';
            if (l2i) l2i.textContent = data.escola.l2_escolha_data_inicio || '';
            if (l2f) l2f.textContent = data.escola.l2_escolha_data_fim || '';
            if (l2e) l2e.textContent = data.escola.l2_entrega_data || '';
            var dlf = document.querySelector('.data_limite_fotos_escola');
            if (dlf) dlf.textContent = data.escola.data_limite_fotos || '';
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
        var nomeInput = document.querySelector('.nome input');
        var escolaInput = document.querySelector('.escola input');
        var unidadeInput = document.querySelector('.unidade input');
        var turmaInput = document.querySelector('.turma input');
        if (nomeInput && data.nome) nomeInput.value = data.nome;
        if (escolaInput && data.escola) escolaInput.value = data.escola.nome || '';
        if (unidadeInput && data.unidade) unidadeInput.value = data.unidade.nome || '';
        if (turmaInput && data.turma) turmaInput.value = data.turma.nome || '';
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
            $('#link_album_shortcode').attr('href', d.link_album);
        }
        if (dataArray.length > 0 && d.fotos_participante && d.fotos_participante.length > 0) {
            var fotosHtml = '<div class="row">';
            $.each(d.fotos_participante, function (index, foto) {
                fotosHtml += '<div class="col-6 col-md-3 col-lg-2 mb-4"><div class="mb-3 fotos-para-selecionar" style="position: relative; text-align: center;"><img src="' + foto.caminho + '" alt="' + foto.nome + '" class="img-fluid mb-4"><button type="button" class="btn btn-primary select-photo text-white" data-codigo="' + foto.codigo + '">Selecionar</button></div></div>';
            });
            fotosHtml += '</div>';
            $('#fotos-container').html(fotosHtml);
            updateCheckboxListener();
        }
        var nomeInput = document.querySelector('.nomeoculto input');
        var escolaeventoInput = document.querySelector('.escolaevento input');
        var eventoInput = document.querySelector('.evento input');
        var escolaInput = document.querySelector('.escola input');
        var unidadeInput = document.querySelector('.unidade input');
        var turmaInput = document.querySelector('.turma input');
        if (nomeInput && d.nome) nomeInput.value = d.nome;
        if (escolaeventoInput && d.escola && d.evento) escolaeventoInput.value = (d.escola.nome || '') + ' / ' + (d.evento.nome || '');
        if (eventoInput && d.evento) eventoInput.value = d.evento.nome || '';
        if (escolaInput && d.escola) escolaInput.value = d.escola.nome || '';
        if (unidadeInput && d.unidade) unidadeInput.value = d.unidade.nome || '';
        if (turmaInput && d.turma) turmaInput.value = d.turma.nome || '';
        var l1ie = document.querySelector('.l1_escolha_data_inicio_evento');
        var l1fe = document.querySelector('.l1_escolha_data_fim_evento');
        var l1ee = document.querySelector('.l1_entrega_data_evento');
        var l2ie = document.querySelector('.l2_escolha_data_inicio_evento');
        var l2fe = document.querySelector('.l2_escolha_data_fim_evento');
        var l2ee = document.querySelector('.l2_entrega_data_evento');
        var l1is = document.querySelector('.l1_escolha_data_inicio_escola');
        var l1fs = document.querySelector('.l1_escolha_data_fim_escola');
        var l1es = document.querySelector('.l1_entrega_data_escola');
        var l2is = document.querySelector('.l2_escolha_data_inicio_escola');
        var l2fs = document.querySelector('.l2_escolha_data_fim_escola');
        var l2es = document.querySelector('.l2_entrega_data_escola');
        if (d.evento) {
            if (d.evento.imagem_logo_evento) {
                $('.imagem_logo_evento').attr('src', d.evento.imagem_logo_evento).attr('srcset', d.evento.imagem_logo_evento);
            }
            if (l1ie) l1ie.textContent = d.evento.l1_escolha_data_inicio || '';
            if (l1fe) l1fe.textContent = d.evento.l1_escolha_data_fim || '';
            if (l1ee) l1ee.textContent = d.evento.l1_entrega_data || '';
            if (l2ie) l2ie.textContent = d.evento.l2_escolha_data_inicio || '';
            if (l2fe) l2fe.textContent = d.evento.l2_escolha_data_fim || '';
            if (l2ee) l2ee.textContent = d.evento.l2_entrega_data || '';
        }
        if (d.escola) {
            if (d.escola.imagem_logo_escola) {
                $('.imagem_logo_escola').attr('src', d.escola.imagem_logo_escola).attr('srcset', d.escola.imagem_logo_escola);
            }
            if (l1is) l1is.textContent = d.escola.l1_escolha_data_inicio || '';
            if (l1fs) l1fs.textContent = d.escola.l1_escolha_data_fim || '';
            if (l1es) l1es.textContent = d.escola.l1_entrega_data || '';
            if (l2is) l2is.textContent = d.escola.l2_escolha_data_inicio || '';
            if (l2fs) l2fs.textContent = d.escola.l2_escolha_data_fim || '';
            if (l2es) l2es.textContent = d.escola.l2_entrega_data || '';
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
            url: `${'https://apreas.com.br'}/wp-admin/admin-ajax.php`,
            data: {
                action: 'process_login_form',
                formData: formData
            },
            dataType: 'json',
            success: function (response) {
                if (response.success == true) {

                    // CAMPOS EXTRAS | ESCOLAS
                    let l1_escolha_data_inicio_escola = document.querySelector('.l1_escolha_data_inicio_escola');
                    let l1_escolha_data_fim_escola = document.querySelector('.l1_escolha_data_fim_escola');
                    let l1_entrega_data_escola = document.querySelector('.l1_entrega_data_escola');
                    let l2_escolha_data_inicio_escola = document.querySelector('.l2_escolha_data_inicio_escola');
                    let l2_escolha_data_fim_escola = document.querySelector('.l2_escolha_data_fim_escola');
                    let l2_entrega_data_escola = document.querySelector('.l2_entrega_data_escola');
                    //  CAMPOS EXTRAS | ESCOLAS

                    if (response.data.escola) {
                        if (response.data.escola.imagem_logo_escola) {
                            $('.imagem_logo_escola').attr('src', response.data.escola.imagem_logo_escola);
                            $('.imagem_logo_escola').attr('srcset', response.data.escola.imagem_logo_escola);
                        }
                        if (l1_escolha_data_inicio_escola) l1_escolha_data_inicio_escola.textContent = response.data.escola.l1_escolha_data_inicio || '';
                        if (l1_escolha_data_fim_escola) l1_escolha_data_fim_escola.textContent = response.data.escola.l1_escolha_data_fim || '';
                        if (l1_entrega_data_escola) l1_entrega_data_escola.textContent = response.data.escola.l1_entrega_data || '';
                        if (l2_escolha_data_inicio_escola) l2_escolha_data_inicio_escola.textContent = response.data.escola.l2_escolha_data_inicio || '';
                        if (l2_escolha_data_fim_escola) l2_escolha_data_fim_escola.textContent = response.data.escola.l2_escolha_data_fim || '';
                        if (l2_entrega_data_escola) l2_entrega_data_escola.textContent = response.data.escola.l2_entrega_data || '';
                        var dlf = document.querySelector('.data_limite_fotos_escola');
                        if (dlf) dlf.textContent = response.data.escola.data_limite_fotos || '';
                    }

                    //console.log(response.data);
                    salvarSessao('escola', response.data);
                    aplicarSessaoEscola(response.data);

                    Swal.fire({
                        title: 'Acesso Liberado com Sucesso!',
                        text: '',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'custom-confirm-button'
                        }
                    });

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

                const nomeInput = document.querySelector('.nome input');
                const eventoInput = document.querySelector('.evento input');
                const escolaInput = document.querySelector('.escola input');
                const unidadeInput = document.querySelector('.unidade input');
                const turmaInput = document.querySelector('.turma input');

                if (nomeInput && response.data.nome) {
                    nomeInput.value = response.data.nome || '';
                    //console.log(nomeInput.value);
                }

                if (eventoInput && response.data.evento) {
                    eventoInput.value = response.data.evento.nome || '';
                    //console.log(eventoInput.value);
                }
                if (escolaInput && response.data.escola) {
                    escolaInput.value = response.data.escola.nome || '';
                    //console.log(escolaInput.value);
                }
                if (unidadeInput && response.data.unidade) {
                    unidadeInput.value = response.data.unidade.nome || '';
                    //console.log(unidadeInput.value);
                }
                if (turmaInput && response.data.turma) {
                    turmaInput.value = response.data.turma.nome || '';
                    //console.log(turmaInput.value);
                }
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
            url: `${'https://apreas.com.br'}/wp-admin/admin-ajax.php`,
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
                        //console.log("Link do Álbum: " + response.data[0].link_album);
                        $('#link_album_shortcode').attr('href', response.data[0].link_album);
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
                        $('#fotos-container').html(fotosHtml);
                        updateCheckboxListener();

                        // ENVIAR VALORES PARA O FORMULÁRIO
                        const nomeInput = document.querySelector('.nomeoculto input');
                        const escolaeventoInput = document.querySelector('.escolaevento input');
                        const eventoInput = document.querySelector('.evento input');
                        const escolaInput = document.querySelector('.escola input');
                        const unidadeInput = document.querySelector('.unidade input');
                        const turmaInput = document.querySelector('.turma input');

                        if (nomeInput && response.data[0].nome) {
                            nomeInput.value = response.data[0].nome || '';
                        }

                        if (escolaeventoInput && response.data[0].escola && response.data[0].evento) {
                            escolaeventoInput.value = `${response.data[0].escola.nome || ''} / ${response.data[0].evento.nome || ''}`;
                        }

                        if (eventoInput && response.data[0].evento) {
                            eventoInput.value = response.data[0].evento.nome || '';
                            //console.log(eventoInput.value);
                        }
                        if (escolaInput && response.data[0].escola) {
                            escolaInput.value = response.data[0].escola.nome || '';
                            //console.log(escolaInput.value);
                        }
                        if (unidadeInput && response.data[0].unidade) {
                            unidadeInput.value = response.data[0].unidade.nome || '';
                            //console.log(unidadeInput.value);
                        }
                        if (turmaInput && response.data[0].turma) {
                            turmaInput.value = response.data[0].turma.nome || '';
                            //console.log(turmaInput.value);
                        }
                        // ENVIAR VALORES PARA O FORMULÁRIO

                    }
                    // FOTOS PARTICIPANTE

                    // CAMPOS EXTRAS | EVENTOS
                    let l1_escolha_data_inicio_evento = document.querySelector('.l1_escolha_data_inicio_evento');
                    let l1_escolha_data_fim_evento = document.querySelector('.l1_escolha_data_fim_evento');
                    let l1_entrega_data_evento = document.querySelector('.l1_entrega_data_evento');
                    let l2_escolha_data_inicio_evento = document.querySelector('.l2_escolha_data_inicio_evento');
                    let l2_escolha_data_fim_evento = document.querySelector('.l2_escolha_data_fim_evento');
                    let l2_entrega_data_evento = document.querySelector('.l2_entrega_data_evento');
                    //  CAMPOS EXTRAS | EVENTOS

                    // CAMPOS EXTRAS | ESCOLAS
                    let l1_escolha_data_inicio_escola = document.querySelector('.l1_escolha_data_inicio_escola');
                    let l1_escolha_data_fim_escola = document.querySelector('.l1_escolha_data_fim_escola');
                    let l1_entrega_data_escola = document.querySelector('.l1_entrega_data_escola');
                    let l2_escolha_data_inicio_escola = document.querySelector('.l2_escolha_data_inicio_escola');
                    let l2_escolha_data_fim_escola = document.querySelector('.l2_escolha_data_fim_escola');
                    let l2_entrega_data_escola = document.querySelector('.l2_entrega_data_escola');
                    //  CAMPOS EXTRAS | ESCOLAS

                    if (response.data[0].evento) {
                        //console.log(response.data[0].evento);
                        if (response.data[0].evento.imagem_logo_evento) {
                            //console.log(response.data[0].evento.imagem_logo_evento);
                            $('.imagem_logo_evento').attr('src', response.data[0].evento.imagem_logo_evento);
                            $('.imagem_logo_evento').attr('srcset', response.data[0].evento.imagem_logo_evento);
                        }
                        if (l1_escolha_data_inicio_evento) l1_escolha_data_inicio_evento.textContent = response.data[0].evento.l1_escolha_data_inicio || '';
                        if (l1_escolha_data_fim_evento) l1_escolha_data_fim_evento.textContent = response.data[0].evento.l1_escolha_data_fim || '';
                        if (l1_entrega_data_evento) l1_entrega_data_evento.textContent = response.data[0].evento.l1_entrega_data || '';
                        if (l2_escolha_data_inicio_evento) l2_escolha_data_inicio_evento.textContent = response.data[0].evento.l2_escolha_data_inicio || '';
                        if (l2_escolha_data_fim_evento) l2_escolha_data_fim_evento.textContent = response.data[0].evento.l2_escolha_data_fim || '';
                        if (l2_entrega_data_evento) l2_entrega_data_evento.textContent = response.data[0].evento.l2_entrega_data || '';
                    }

                    if (response.data[0].escola) {
                        if (response.data[0].escola.imagem_logo_escola) {
                            $('.imagem_logo_escola').attr('src', response.data[0].escola.imagem_logo_escola);
                            $('.imagem_logo_escola').attr('srcset', response.data[0].escola.imagem_logo_escola);
                        }
                        if (l1_escolha_data_inicio_escola) l1_escolha_data_inicio_escola.textContent = response.data[0].escola.l1_escolha_data_inicio || '';
                        if (l1_escolha_data_fim_escola) l1_escolha_data_fim_escola.textContent = response.data[0].escola.l1_escolha_data_fim || '';
                        if (l1_entrega_data_escola) l1_entrega_data_escola.textContent = response.data[0].escola.l1_entrega_data || '';
                        if (l2_escolha_data_inicio_escola) l2_escolha_data_inicio_escola.textContent = response.data[0].escola.l2_escolha_data_inicio || '';
                        if (l2_escolha_data_fim_escola) l2_escolha_data_fim_escola.textContent = response.data[0].escola.l2_escolha_data_fim || '';
                        if (l2_entrega_data_escola) l2_entrega_data_escola.textContent = response.data[0].escola.l2_entrega_data || '';
                    }

                    Swal.fire({
                        title: 'Acesso Liberado com Sucesso!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });

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
            const countElement = document.getElementById('selected-count');
            if (countElement) {
                countElement.textContent = count; // Atualiza o texto do contador
            }
        }

        // Atualiza o input com os valores selecionados
        function updateInputEscolha() {
            const selected = getSelectedValues();
            const inputEscolha = document.querySelector('.escolha input');
            if (inputEscolha) {
                inputEscolha.value = selected;
            } else {
                console.log('Nenhum input foi encontrado dentro do div .escolha.');
            }
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
                    document.getElementById('fotos-container').appendChild(input);
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
        location.reload();
    });
    // SAIR / LOGOUT

});
