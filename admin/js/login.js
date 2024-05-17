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
            url: `${'http://127.0.0.1/apreas'}/wp-admin/admin-ajax.php`,
            data: {
                action: 'process_login_form', 
                formData: formData
            },
            dataType: 'json',
            success: function(response) {
                console.log(response);
                if (response.success == true) {                    
                    Swal.fire({
                        title: response.data,
                        text: '',
                        icon: 'success',
                    });
                } 
                if (response.success == false) {                    
                    Swal.fire({
                        title: response.data,
                        text: '',
                        icon: 'error',
                    });
                } 
                
            },
            error: function(response) {
                console.log(response);
                alert('Ocorreu um erro.');
            }
        });
    });
});
