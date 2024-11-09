jQuery(function($){
    $('body').on('click', '.imagem_upload_individual_btn', function(e){
        e.preventDefault();
        var button = $(this),
        aw_uploader = wp.media({
            title: 'Imagem para a Foto Individual',
            library : {
                
                type : 'image'
            },
            button: {
                text: 'Selecionar esta Imagem'
            },
            multiple: false
        }).on('select', function() {
            var attachment = aw_uploader.state().get('selection').first().toJSON();
            $('#imagem_upload_individual').val(attachment.url);
            $('.preview-aluno-individual').css('background-image', 'url(' + attachment.url + ')');
        })
        .open();
    });
    $('body').on('click', '.imagem_upload_individual_btn2', function(e){
        e.preventDefault();
        var button = $(this),
        aw_uploader = wp.media({
            title: 'Imagem para a Foto Individual 2',
            library : {
                
                type : 'image'
            },
            button: {
                text: 'Selecionar esta Imagem'
            },
            multiple: false
        }).on('select', function() {
            var attachment = aw_uploader.state().get('selection').first().toJSON();
            $('#imagem_upload_individual2').val(attachment.url);
            $('.preview-aluno-individual2').css('background-image', 'url(' + attachment.url + ')');
        })
        .open();
    });
    $('body').on('click', '.imagem_upload_turma_btn', function(e){
        e.preventDefault();
        var button = $(this),
        aw_uploader = wp.media({
            title: 'Imagem para a Foto em Turma',
            library : {
                
                type : 'image'
            },
            button: {
                text: 'Selecionar esta Imagem'
            },
            multiple: false
        }).on('select', function() {
            var attachment = aw_uploader.state().get('selection').first().toJSON();
            $('#imagem_upload_turma').val(attachment.url);
            $('.preview-aluno-turma').css('background-image', 'url(' + attachment.url + ')');
        })
        .open();
    });
});
