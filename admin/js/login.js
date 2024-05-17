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
                    console.log(response.data);
                } 
                if (response.success == false) {    
                    console.log(response.data);                
                } 
            },
            error: function(response) {
                console.log(response.data);
            }
        });
    });
});
