jQuery(document).ready(function($) {

    // LIMPAR ESPAÇOS EM BRANCO DO INPUT SENHA_NOME
    let timeout;
    document.getElementById("senha_nome").addEventListener("input", function () {
        clearTimeout(timeout); 
        timeout = setTimeout(() => {
            this.value = this.value.trim(); 
        }, 500); 
    });
    // LIMPAR ESPAÇOS EM BRANCO DO INPUT SENHA_NOME

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
        $.each(formDataArray, function() {
            formData[this.name] = this.value;
        });
        $.ajax({
            type: 'POST',
            url: `${'https://apreas.com.br'}/wp-admin/admin-ajax.php`,
            data: {
                action: 'process_login_form', 
                formData: formData
            },
            dataType: 'json',
            success: function(response) {
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
                    }

                    console.log(response.data);
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
                    console.log(nomeInput.value);
                }

                if (eventoInput && response.data.evento) {
                    eventoInput.value = response.data.evento.nome || '';
                    console.log(eventoInput.value);
                }
                if (escolaInput && response.data.escola) {
                    escolaInput.value = response.data.escola.nome || '';
                    console.log(escolaInput.value);
                }
                if (unidadeInput && response.data.unidade) {
                    unidadeInput.value = response.data.unidade.nome || '';
                    console.log(unidadeInput.value);
                }
                if (turmaInput && response.data.turma) {
                    turmaInput.value = response.data.turma.nome || '';
                    console.log(turmaInput.value);
                }
                // ENVIAR VALORES PARA O FORMULÁRIO

            },
            error: function(response) {
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
        $.each(formDataArray, function() {
            formData[this.name] = this.value;
        });
    
        $.ajax({
            type: 'POST',
            url: `${'https://apreas.com.br'}/wp-admin/admin-ajax.php`,
            data: {
                action: 'process_login_form_eventos', 
                formData: formData
            },
            dataType: 'json',
            success: function(response) {
                console.log(response)
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
                        console.log("Link do Álbum: "+response.data[0].link_album);
                        $('#link_album_shortcode').attr('href', response.data[0].link_album);
                    }       
                    // LINK ALBUM

                    
                    // FOTOS PARTICIPANTE
                    if (response.data.length > 0 && response.data[0].fotos_participante) {
                        var fotosHtml = '<div class="row">';
                        $.each(response.data[0].fotos_participante, function(index, foto) {
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

                        if (eventoInput&& response.data[0].evento) {
                            eventoInput.value = response.data[0].evento.nome || '';
                            console.log(eventoInput.value);
                        }
                        if (escolaInput && response.data[0].escola) {
                            escolaInput.value = response.data[0].escola.nome || '';
                            console.log(escolaInput.value);
                        }
                        if (unidadeInput && response.data[0].unidade) {
                            unidadeInput.value = response.data[0].unidade.nome || '';
                            console.log(unidadeInput.value);
                        }
                        if (turmaInput && response.data[0].turma) {
                            turmaInput.value = response.data[0].turma.nome || '';
                            console.log(turmaInput.value);
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
                        console.log(response.data[0].evento);
                        if (response.data[0].evento.imagem_logo_evento) {
                            console.log(response.data[0].evento.imagem_logo_evento);
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
                    window.scrollTo(0, 0);
                } else {
                    Swal.fire({
                        title: 'Participante não encontrado.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });             
                }
            },
            error: function(response) {
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

});
