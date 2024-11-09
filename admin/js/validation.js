 document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('form_login');
    const form_evento = document.getElementById('form_login_eventos');

    const name = document.getElementById('senha_nome');
    const data_nascimento = document.getElementById('data_nascimento');
    const escola = document.getElementById('escola');
    const evento = document.getElementById('evento');

    const nameError = document.getElementById('nameError');
    const dataError = document.getElementById('dataError');
    const escolaError = document.getElementById('escolaError');
    const eventoError = document.getElementById('eventoError');

    if(form!=null){
        form.addEventListener('submit', (e) => {
            validateName(e);
            validateData(e);
            validateEscola(e);
        }) 

        name.addEventListener('input', validateName);
        name.addEventListener('focus', validateName);

        data_nascimento.addEventListener('input', validateData);
        data_nascimento.addEventListener('focus', validateData);

        jQuery(escola).on('change', function(e) {
            validateEscola(e);
        });

        jQuery(escola).on('focus', function(e) {
            validateEscola(e);
        });

        jQuery(escola).on('blur', function(e) {
            validateEscola(e);
        });

        function validateName(e){
            if (name.value === '' || name.value == null) {
                name.classList.add("is-invalid");
                nameError.classList.add("d-block");
                nameError.innerText = 'Insira algo no Nome e Último Nome';
                e.preventDefault();
            } else {
                name.classList.remove("is-invalid");
                name.classList.add("is-valid");
                nameError.classList.remove("d-block");
            }
        }
        function validateData(e){
            if (data_nascimento.value === '' || data_nascimento.value == null) {
                data_nascimento.classList.add("is-invalid");
                dataError.classList.add("d-block");
                dataError.innerText = 'Insira algo na Data de Nascimento';
                e.preventDefault();
            } else {
                data_nascimento.classList.remove("is-invalid");
                data_nascimento.classList.add("is-valid");
                dataError.classList.remove("d-block");
            }
        }
        function validateEscola(e){
            const escola_select = document.querySelector('.form-select');
            if (escola.value === '' || escola.value == null) {
                escola_select.style.border = "1px solid #dc3545"; 
                escolaError.classList.add("d-block");
                escolaError.innerText = 'Escolha uma Escola';
                e.preventDefault();
            } else {
                escola_select.style.border = "1px solid #198754"; 
                escolaError.classList.remove("d-block");
            }
        }
    }else{
        if(form_evento!="" || form_evento!=undefined || form_evento!=null){
            form_evento.addEventListener('submit', (e) => {
                validateName(e);
                validateData(e);
                validateEvento(e);
            }) 

            name.addEventListener('input', validateName);
            name.addEventListener('focus', validateName);

            data_nascimento.addEventListener('input', validateData);
            data_nascimento.addEventListener('focus', validateData);

            jQuery(evento).on('change', function(e) {
                validateEvento(e);
            });

            jQuery(evento).on('focus', function(e) {
                validateEvento(e);
            });

            jQuery(evento).on('blur', function(e) {
                validateEvento(e);
            });

            function validateName(e){
                if (name.value === '' || name.value == null) {
                    name.classList.add("is-invalid");
                    nameError.classList.add("d-block");
                    nameError.innerText = 'Insira algo no Nome e Último Nome';
                    e.preventDefault();
                } else {
                    name.classList.remove("is-invalid");
                    name.classList.add("is-valid");
                    nameError.classList.remove("d-block");
                }
            }
            function validateData(e){
                if (data_nascimento.value === '' || data_nascimento.value == null) {
                    data_nascimento.classList.add("is-invalid");
                    dataError.classList.add("d-block");
                    dataError.innerText = 'Insira algo na Data de Nascimento';
                    e.preventDefault();
                } else {
                    data_nascimento.classList.remove("is-invalid");
                    data_nascimento.classList.add("is-valid");
                    dataError.classList.remove("d-block");
                }
            }
            function validateEvento(e){
                const evento_select = document.querySelector('.form-select');
                if (evento.value === '' || evento.value == null) {
                    evento_select.style.border = "1px solid #dc3545"; 
                    eventoError.classList.add("d-block");
                    eventoError.innerText = 'Escolha um Evento';
                    e.preventDefault();
                } else {
                    evento_select.style.border = "1px solid #198754"; 
                    eventoError.classList.remove("d-block");
                }
            }
        }    
    }
})
    