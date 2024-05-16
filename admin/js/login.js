jQuery(document).ready(function($) {

    $('#form_login').submit(function (e) { 
        e.preventDefault(); // Impede o envio padrão do formulário
       // Obtém os dados do formulário
       var formDataArray = $(this).serializeArray();
       var formData = {};

       $.each(formDataArray, function() {
           formData[this.name] = this.value;
       });

        $.ajax({
            type: 'POST',
            url: `${'http://127.0.0.1/apreas'}/wp-admin/admin-ajax.php`,
            data: {
                action: 'process_login_form', 
                formData: formData
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log(JSON.stringify(response.data.formData)); 
                } 
            },
            error: function(response) {
                console.log(response);
                alert('Ocorreu um erro.');
            }
        });
    });
});
