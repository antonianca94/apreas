jQuery(document).ready(function($) {

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
                    console.log(response.data);
                    Swal.fire({
                        title: 'Acesso Liberado com Sucesso!',
                        text: '',
                        icon: 'success',
                        confirmButtonText: 'OK'
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
                        confirmButtonText: 'OK'
                    });             
                } 
                
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
                            <div class="col-md-4 mb-4">
                                <div class="mb-3" style="position: relative; text-align: center;">
                                    <label class="foto-item">
                                        <input type="checkbox" name="selected_photos[]" value="${foto.codigo}" class="checkbox-photo">
                                        <img src="${foto.caminho}" alt="${foto.nome}" class="img-fluid">
                                    </label>
                                </div>
                            </div>
                            `;            
                        });
                        fotosHtml += '</div>';
                        $('#fotos-container').html(fotosHtml);
                        updateCheckboxListener();
                    }
                    // FOTOS PARTICIPANTE
        
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
        function getSelectedCheckboxes() {
            let selectedValues = [];
            const checkboxes = document.querySelectorAll('input[name="selected_photos[]"]:checked');
            checkboxes.forEach(function(checkbox) {
                selectedValues.push(checkbox.value);
            });
            return selectedValues.join(','); 
        }
        document.querySelectorAll('input[name="selected_photos[]"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const selected = getSelectedCheckboxes();
                console.log(selected); 
                const inputEscolha = document.querySelector('.escolha input');
                    if (inputEscolha) {
                        inputEscolha.value = selected; 
                    } else {
                        console.log('Nenhum input foi encontrado dentro do div .escolha.');
                    }
                });
        });
    }
    updateCheckboxListener();
    // FUNÇÃO QUE COLOCA OS VALORES DAS FOTOS NO INPUT DO FORMULÁRIO

});
