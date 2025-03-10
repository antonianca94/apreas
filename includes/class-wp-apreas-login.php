<?php
namespace Apreas;
    session_start();

if ( ! defined( 'ABSPATH' ) ) exit;

class Login {
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {      
        add_shortcode( 'login_form', [$this,'render_login_form'] );
        add_action('wp_ajax_process_login_form', [$this,'process_login_form']);
        add_action('wp_ajax_nopriv_process_login_form', [$this,'process_login_form']);

        add_shortcode( 'login_form_eventos', [$this,'render_login_form_eventos'] );
        add_action('wp_ajax_process_login_form_eventos', [$this,'process_login_form_eventos']);
        add_action('wp_ajax_nopriv_process_login_form_eventos', [$this,'process_login_form_eventos']);

        add_shortcode( 'galeria', [$this,'shortcode_fotos_container'] );
        add_shortcode( 'fotos_selecionadas', [$this,'fotos_counter'] );

        // LOGOS
        add_shortcode('imagem_logo_evento', [$this,'mostrar_imagem_logo_evento']);
        add_shortcode('imagem_logo_escola', [$this,'mostrar_imagem_logo_escola']);
        // LOGOS

        // ESCOLAS
        add_shortcode('lotes_escola', [$this,'mostrar_lotes_escolas']);
        // ESCOLAS

        // EVENTOS
        add_shortcode('lotes_evento', [$this,'mostrar_lotes_eventos']);
        // EVENTOS
    }


    function mostrar_lotes_eventos() {
        return '
            <div class="d-flex justify-content-start align-items-center">
                <div class="w-100 p-3 m-3" style="border: 1px solid grey; border-radius: 1rem;">
                    <h3 style="padding-bottom: 0.5rem; font-weight: 700;">Lote 1</h3>
                    <h5>Escolha</h5>
                    <p style="font-size: 1.2rem;"> <span class="l1_escolha_data_inicio_evento"> </span> à <span class="l1_escolha_data_fim_evento"> </span> </p>
                    <h5>Entrega</h5>
                    <p style="font-size: 1.2rem;"> <span class="l1_entrega_data_evento"> </span> </p>
                </div>
                <div class="w-100 p-3 m-3" style="border: 1px solid grey; border-radius: 1rem;">
                    <h3 style="padding-bottom: 0.5rem; font-weight: 700;">Lote 2</h3>
                    <h5>Escolha</h5>
                    <p style="font-size: 1.2rem;"> <span class="l2_escolha_data_inicio_evento"> </span> à <span class="l2_escolha_data_fim_evento"> </span> </p>
                    <h5>Entrega</h5>
                    <p style="font-size: 1.2rem;"> <span class="l2_entrega_data_evento"> </span> </p>
                </div>
            </div>
        ';
    }

    function mostrar_lotes_escolas() {
        return '
            <div class="d-flex justify-content-start align-items-center">
                <div class="w-100 p-3 m-3" style="border: 1px solid grey; border-radius: 1rem;">
                    <h3 style="padding-bottom: 0.5rem; font-weight: 700;">Lote 1</h3>
                    <h5>Escolha</h5>
                    <p style="font-size: 1.2rem;"> <span class="l1_escolha_data_inicio_escola"> </span> à <span class="l1_escolha_data_fim_escola"> </span> </p>
                    <h5>Entrega</h5>
                    <p style="font-size: 1.2rem;"> <span class="l1_entrega_data_escola"> </span> </p>
                </div>
                <div class="w-100 p-3 m-3" style="border: 1px solid grey; border-radius: 1rem;">
                    <h3 style="padding-bottom: 0.5rem; font-weight: 700;">Lote 2</h3>
                    <h5>Escolha</h5>
                    <p style="font-size: 1.2rem;"> <span class="l2_escolha_data_inicio_escola"> </span> à <span class="l2_escolha_data_fim_escola"> </span> </p>
                    <h5>Entrega</h5>
                    <p style="font-size: 1.2rem;"> <span class="l2_entrega_data_escola"> </span> </p>
                </div>
            </div>
        ';
    }
    
    function mostrar_imagem_logo_evento() {
        return '<img class="imagem_logo_evento" src="https://apreas.com.br/wp-content/plugins/elementor/assets/images/placeholder.png" alt="Logo do Evento" style="max-width: 100%; height: auto;">';
    }

    function mostrar_imagem_logo_escola() {
        return '<img class="imagem_logo_escola" src="https://apreas.com.br/wp-content/plugins/elementor/assets/images/placeholder.png" alt="Logo da Escola" style="max-width: 100%; height: auto;">';
    }

    function fotos_counter() {
        ob_start(); 
        ?>
        <div id="selected-count-container" class="text-center" style="font-size: 2rem; font-weight: 400;font-family: 'Roboto', Sans-serif !important;color: grey;">
            <span id="selected-count">0</span>
        </div>
        <?php
        return ob_get_clean(); 
    }

    function shortcode_fotos_container() {
        ob_start(); 
        ?>
        <div id="fotos-container" class="container">
        </div>
        <?php
        return ob_get_clean(); 
    }

    function render_login_form() {
        ob_start(); ?>
        
        <div id="loginContainer">
            <div id="loginOverlay"></div>
            <div id="loginContent">
                <a type="button" class="btn btn-secondary back" style="" onclick="window.history.back()">
                    <i class="bi bi-arrow-left pe-1" style="font-size: 1.5rem !important; padding-right: 0.7rem !important;"></i> Voltar
                </a>
                <form class="row g-3" id="form_login" name="form_login" enctype="multipart/form-data">
                    <div class="col-md-6">
                        <label for="senha_nome" class="form-label"> Nome e Último Nome </label>
                        <input type="text" class="form-control senha_nome" name="senha_nome" id="senha_nome" placeholder="Nome e Último Nome">
                        <div class="invalid-feedback" id="nameError"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                        <input type="date" class="form-control data_nascimento" name="data_nascimento" id="data_nascimento" placeholder="Data de Nascimento">
                        <div class="invalid-feedback" id="dataError"></div>
                    </div>
                    <div class="col-12">
                        <label for="escola" class="form-label">Escola</label>
                        <select class="form-select" name="escola" id="escola" aria-label="Escola">
                        <option value=""> </option>
                        <?php
                            $escolas = get_posts( array(
                                'post_type' => 'escolas',
                                'posts_per_page' => -1, 
                            ) );
                            foreach ( $escolas as $escola ) {
                                echo '<option value="' . esc_attr( $escola->ID ) . '">' . esc_html( $escola->post_title ) . '</option>';
                            }
                        ?>
                        </select>       
                        <div class="invalid-feedback" id="escolaError"></div> 
                    </div>
                    <div class="col-12">
                        Dúvidas e Problemas: (11) 93949-0911
                    </div>                     
                    <div class="col-12 mt-4 pt-3 pb-3">
                        <button type="submit" class="btn btn-primary btn_form_login">Acessar</button>
                    </div>
                </form>
            </div>
        </div>
    
        <!-- CSS -->
        <style>
            .back {
                font-family: "Roboto", Sans-serif !important; 
                padding: 25px 0px !important; 
                padding-top: 0 !important;
                border: 0 !important; 
                box-shadow: none !important; 
                background: none !important;
                font-weight: 500 !important;
                text-transform: uppercase !important;
                font-size: 1.2rem !important;
                display: flex;
                align-items: center;
            }
            .back:hover {
                color: black !important;
            }
            #loginContainer {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10;
            }
    
            #loginOverlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgb(0 0 0 / 88%);
                z-index: 1;
            }
    
            #loginContent {
                position: relative;
                z-index: 2;
                background-color: #fff; 
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.5); 
            }
            form#form_login input:not(.is-invalid, .is-valid) {
                border: 1px solid #c2c2c2 !important;
                border-radius: 5px !important;
            }
            form#form_login select:not(.is-invalid, .is-valid) {
                font-size: 15px !important;
                line-height: 1rem !important;
            }
            .woocommerce-js form#form_login input, .woocommerce-js form#form_login select {
                font-family: "Roboto", Sans-serif !important;
                padding-left: 1rem !important;
            }
 
            .btn_form_login {
                background: #C0FF2D !important;
                color: #000 !important;
                font-weight: 500 !important;
                border: none !important;
                text-transform: uppercase !important;
                margin-top: 8px;
                padding: 8px 17px !important;
                font-family: "Roboto", Sans-serif !important;
                font-size: 1.2rem;
            }

            .btn_form_login:focus, .btn_form_login:hover{
                background: #006DD4 !important;
            }

            .woocommerce-js form#form_login input.is-invalid {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-invalid:active {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-invalid:focus {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid:active {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid:focus {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }


            .woocommerce-js form#form_login select.is-invalid {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-invalid:active {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-invalid:focus {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid:active {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid:focus {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
        </style>
        <!-- CSS -->
        
        <?php
        return ob_get_clean();
    }
    
    public function process_login_form() {
        $senha_nome = $_POST['formData']['senha_nome'];
        $senha_nome_transformada = strtolower(str_replace(' ', '_', $senha_nome));
        $data_nascimento = $_POST['formData']['data_nascimento'];
        $escola = $_POST['formData']['escola'];
    
        $args = array(
            'post_type' => 'alunos',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'senha_nome',
                    'value' => $senha_nome_transformada,
                    'compare' => '='
                ),
                array(
                    'key' => 'data_nascimento',
                    'value' => $data_nascimento,
                    'compare' => '='
                ),
                array(
                    'key' => 'escola',
                    'value' => $escola,
                    'compare' => '='
                )
            )
        );
    
        $query = new \WP_Query($args);
        foreach ($query->posts as $post) {
            $post_id = $post->ID;
            $imagem_upload_individual = get_post_meta($post_id, 'imagem_upload_individual', true);
            $imagem_upload_individual2 = get_post_meta($post_id, 'imagem_upload_individual2', true);
            $imagem_upload_turma = get_post_meta($post_id, 'imagem_upload_turma', true);

            // [ESCOLA - UNIDADE - TURMA] 
                $escola_id = get_post_meta($post_id, 'escola', true);    
                $unidade_id = get_post_meta($post_id, 'unidade', true);
                $turma_id = get_post_meta($post_id, 'turma', true);
                $escola_post = get_post($escola_id);
                $unidade_post = get_post($unidade_id);
                $turma_post = get_post($turma_id);
            // [ESCOLA - UNIDADE - TURMA] 

            // ESCOLA | CAMPOS EXTRAS
            $imagem_logo_escola = get_post_meta(intval($escola_id), 'imagem_logo_escola', true);
            $l1_escolha_data_inicio_escola = get_post_meta(intval($escola_id), 'l1_escolha_data_inicio', true);
            $l1_escolha_data_fim_escola = get_post_meta(intval($escola_id), 'l1_escolha_data_fim', true);
            $l1_entrega_data_escola = get_post_meta(intval($escola_id), 'l1_entrega_data', true);
            $l2_escolha_data_inicio_escola = get_post_meta(intval($escola_id), 'l2_escolha_data_inicio', true);
            $l2_escolha_data_fim_escola = get_post_meta(intval($escola_id), 'l2_escolha_data_fim', true);
            $l2_entrega_data_escola = get_post_meta(intval($escola_id), 'l2_entrega_data', true);
            // ESCOLA | CAMPOS EXTRAS

            $dados = [
                'nome' => $post->post_title,
                'imagem_upload_individual' => $imagem_upload_individual,
                'imagem_upload_individual2' => $imagem_upload_individual2,
                'imagem_upload_turma' => $imagem_upload_turma,
                'escola' => [
                    'id' => intval($escola_id),
                    'nome' => $escola_post ? $escola_post->post_title : null,
                    'imagem_logo_escola' => $imagem_logo_escola,
                    'l1_escolha_data_inicio' => $l1_escolha_data_inicio_escola,
                    'l1_escolha_data_fim' => $l1_escolha_data_fim_escola,
                    'l1_entrega_data' => $l1_entrega_data_escola,
                    'l2_escolha_data_inicio' => $l2_escolha_data_inicio_escola,
                    'l2_escolha_data_fim' => $l2_escolha_data_fim_escola,
                    'l2_entrega_data' => $l2_entrega_data_escola
                ],
                'unidade' => [
                    'id' => intval($unidade_id),
                    'nome' => $unidade_post ? $unidade_post->post_title : null
                ],
                'turma' => [
                    'id' => intval($turma_id),
                    'nome' => $turma_post ? $turma_post->post_title : null
                ]
            ];
        }
        if ($query->have_posts()) {
            wp_send_json_success($dados);
        } else {
            wp_send_json_error($dados);
        }
    }

    function render_login_form_eventos($atts) {
        // Recebendo o parâmetro da categoria do evento no shortcode
        $atts = shortcode_atts(array(
            'categoria' => '',  // Valor padrão
        ), $atts);
    

        ob_start(); ?>
        
        <div id="loginContainer">
            <div id="loginOverlay"></div>
            <div id="loginContent">
                <a type="button" class="btn btn-secondary back" onclick="window.history.back()">
                    <i class="bi bi-arrow-left pe-1" style="font-size: 1.5rem !important; padding-right: 0.7rem !important;"></i> Voltar
                </a>
                <form class="row g-3" id="form_login_eventos" name="form_login_eventos" enctype="multipart/form-data">
                    <div class="col-md-6">
                        <label for="senha_nome" class="form-label"> Nome e Último Nome </label>
                        <input type="text" class="form-control senha_nome" name="senha_nome" id="senha_nome" placeholder="Nome e Último Nome">
                        <div class="invalid-feedback" id="nameError"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="data_nascimento" class="form-label">Data de Nascimento / Data Evento</label>
                        <input type="date" class="form-control data_nascimento" name="data_nascimento" id="data_nascimento" placeholder="Data de Nascimento">
                        <div class="invalid-feedback" id="dataError"></div>
                    </div>
                    <div class="col-12">
                        <label for="evento" class="form-label">Evento</label>
                        <select class="form-select" name="evento" id="evento" aria-label="Evento">
                        <option value=""> </option>
                        <?php

                            // Query para buscar os eventos
                            $args = array(
                                'post_type'             => 'eventos',
                                'posts_per_page'        => -1,
                                'post_status'           => 'publish',
                            );

                            $eventos = get_posts($args);

                            foreach ($eventos as $evento) {
                                $categorias_eventos = get_post_field( 'categorias_eventos', $evento->ID );
                                foreach ($categorias_eventos as $c) {
                                    if($c == $atts['categoria']){
                                        echo '<option value="' . esc_attr($evento->ID) . '">' . esc_html($evento->post_title) . '</option>';
                                    }
                                }
                            }
                        ?>
                        </select>       
                        <div class="invalid-feedback" id="eventoError"></div>     
                    </div>
                    <div class="col-12">
                        Dúvidas e Problemas: (11) 93949-0911
                    </div>                      
                    <div class="col-12 mt-4 pt-3 pb-3">
                        <button type="submit" class="btn btn-primary btn_form_login">Acessar</button>
                    </div>
                </form>
            </div>
        </div>
    
        <style>
            .back {
                font-family: "Roboto", Sans-serif !important; 
                padding: 25px 0px !important; 
                padding-top: 0 !important;
                border: 0 !important; 
                box-shadow: none !important; 
                background: none !important;
                font-weight: 500 !important;
                text-transform: uppercase !important;
                font-size: 1.2rem !important;
                display: flex;
                align-items: center;
            }
            .back:hover {
                color: black !important;
            }
            #loginContainer {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10;
            }
    
            #loginOverlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgb(0 0 0 / 88%);
                z-index: 1;
            }
    
            #loginContent {
                position: relative;
                z-index: 2;
                background-color: #fff; 
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.5); 
            }
            form#form_login input:not(.is-invalid, .is-valid) {
                border: 1px solid #c2c2c2 !important;
                border-radius: 5px !important;
            }
            form#form_login select:not(.is-invalid, .is-valid) {
                font-size: 15px !important;
                line-height: 1rem !important;
            }
            .woocommerce-js form#form_login input, .woocommerce-js form#form_login select {
                font-family: "Roboto", Sans-serif !important;
                padding-left: 1rem !important;
            }
 
            .btn_form_login {
                background: #C0FF2D !important;
                color: #000 !important;
                font-weight: 500 !important;
                border: none !important;
                text-transform: uppercase !important;
                margin-top: 8px;
                padding: 8px 17px !important;
                font-family: "Roboto", Sans-serif !important;
                font-size: 1.2rem;
            }

            .btn_form_login:focus, .btn_form_login:hover{
                background: #006DD4 !important;
            }

            .woocommerce-js form#form_login input.is-invalid {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-invalid:active {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-invalid:focus {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid:active {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid:focus {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }


            .woocommerce-js form#form_login select.is-invalid {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-invalid:active {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-invalid:focus {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid:active {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid:focus {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }

        </style>
        
        <?php
        return ob_get_clean();
    }
    
    // public function process_login_form_eventos() {
    //     $senha_nome = $_POST['formData']['senha_nome'];
    //     $senha_nome_transformada = strtolower(str_replace(' ', '_', $senha_nome));
    //     $data_nascimento = $_POST['formData']['data_nascimento'];
    //     $evento = $_POST['formData']['evento'];
    
    //     $args = array(
    //         'post_type' => 'participantes',
    //         'meta_query' => array(
    //             'relation' => 'AND',
    //             array(
    //                 'key' => 'senha_nome',
    //                 'value' => $senha_nome_transformada,
    //                 'compare' => '='
    //             ),
    //             array(
    //                 'key' => 'data_nascimento',
    //                 'value' => $data_nascimento,
    //                 'compare' => '='
    //             ),
    //             array(
    //                 'key' => 'evento',
    //                 'value' => $evento,
    //                 'compare' => '='
    //             )
    //         )
    //     );
    
    //     $query = new \WP_Query($args);
    //     foreach ($query->posts as $post) {
    //         $post_id = $post->ID;
    //         $fotos = get_post_meta($post_id, '_fotos_participantes', true) ?: array(); // Garantir que $fotos seja um array
    //         $dados = [
    //             'fotos_participante' => $fotos
    //         ];
    //     }
    //     if ($query->have_posts()) {
    //         wp_send_json_success($dados);
    //     } else {
    //         wp_send_json_error($dados);
    //     }
    // }

    public function process_login_form_eventos() {
        $senha_nome = $_POST['formData']['senha_nome'];
        $senha_nome_transformada = strtolower(str_replace(' ', '_', $senha_nome));
        $data_nascimento = $_POST['formData']['data_nascimento'];
        $evento = $_POST['formData']['evento'];
    
        
        $args = array(
            'post_type' => 'participantes',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'senha_nome',
                    'value' => $senha_nome_transformada,
                    'compare' => '='
                ),
                array(
                    'key' => 'data_nascimento',
                    'value' => $data_nascimento,
                    'compare' => '='
                ),
                array(
                    'key' => 'evento',
                    'value' => $evento,
                    'compare' => '='
                )
            )
        );
    
        $query = new \WP_Query($args);
        $dados = array(); // Inicializar array de dados
    
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $post_id = $post->ID;
                $fotos = get_post_meta($post_id, '_fotos_participantes', true) ?: array(); // Garantir que $fotos seja um array
                
                $imagem_upload_individual = get_post_meta($post_id, 'imagem_upload_individual', true);
                $imagem_upload_individual2 = get_post_meta($post_id, 'imagem_upload_individual2', true);
                $imagem_upload_turma = get_post_meta($post_id, 'imagem_upload_turma', true);
                $link_album = get_post_meta($post_id, 'link_album', true);

                // [ESCOLA - UNIDADE - TURMA] 
                    $evento_id = get_post_meta($post_id, 'evento', true);    
                    $escola_id = get_post_meta($post_id, 'escola', true);    
                    $unidade_id = get_post_meta($post_id, 'unidade', true);
                    $turma_id = get_post_meta($post_id, 'turma', true);
                    $evento_post = get_post($evento_id);
                    $escola_post = get_post($escola_id);
                    $unidade_post = get_post($unidade_id);
                    $turma_post = get_post($turma_id);
                // [ESCOLA - UNIDADE - TURMA] 

                // Montar as fotos com nome, caminho e código
                $fotos_formatadas = array();
                foreach ($fotos as $foto) {
                    $fotos_formatadas[] = array(
                        'nome' => $foto['nome'],
                        'caminho' => $foto['caminho'],
                        'codigo' => $foto['codigo']
                    );
                }

                // ESCOLA | CAMPOS EXTRAS
                $imagem_logo_escola = get_post_meta(intval($escola_id), 'imagem_logo_escola', true);
                $l1_escolha_data_inicio_escola = get_post_meta(intval($escola_id), 'l1_escolha_data_inicio', true);
                $l1_escolha_data_fim_escola = get_post_meta(intval($escola_id), 'l1_escolha_data_fim', true);
                $l1_entrega_data_escola = get_post_meta(intval($escola_id), 'l1_entrega_data', true);
                $l2_escolha_data_inicio_escola = get_post_meta(intval($escola_id), 'l2_escolha_data_inicio', true);
                $l2_escolha_data_fim_escola = get_post_meta(intval($escola_id), 'l2_escolha_data_fim', true);
                $l2_entrega_data_escola = get_post_meta(intval($escola_id), 'l2_entrega_data', true);
                // ESCOLA | CAMPOS EXTRAS

                // EVENTO | CAMPOS EXTRAS
                $imagem_logo_evento = get_post_meta(intval($evento_id), 'imagem_logo_evento', true);
                $l1_escolha_data_inicio_evento = get_post_meta(intval($evento_id), 'l1_escolha_data_inicio', true);
                $l1_escolha_data_fim_evento = get_post_meta(intval($evento_id), 'l1_escolha_data_fim', true);
                $l1_entrega_data_evento = get_post_meta(intval($evento_id), 'l1_entrega_data', true);
                $l2_escolha_data_inicio_evento = get_post_meta(intval($evento_id), 'l2_escolha_data_inicio', true);
                $l2_escolha_data_fim_evento = get_post_meta(intval($evento_id), 'l2_escolha_data_fim', true);
                $l2_entrega_data_evento = get_post_meta(intval($evento_id), 'l2_entrega_data', true);
                // EVENTO | CAMPOS EXTRAS
    
                $dados[] = [
                    'nome' => $post->post_title,
                    'fotos_participante' => $fotos_formatadas,
                    'imagem_upload_individual' => $imagem_upload_individual,
                    'imagem_upload_individual2' => $imagem_upload_individual2,
                    'imagem_upload_turma' => $imagem_upload_turma,
                    'link_album' => $link_album,
                    'evento' => [
                        'id' => intval($evento_id),
                        'nome' => $evento_post ? $evento_post->post_title : null,
                        'imagem_logo_evento' => $imagem_logo_evento,
                        'l1_escolha_data_inicio' => $l1_escolha_data_inicio_evento,
                        'l1_escolha_data_fim' => $l1_escolha_data_fim_evento,
                        'l1_entrega_data' => $l1_entrega_data_evento,
                        'l2_escolha_data_inicio' => $l2_escolha_data_inicio_evento,
                        'l2_escolha_data_fim' => $l2_escolha_data_fim_evento,
                        'l2_entrega_data' => $l2_entrega_data_evento
                    ],
                    'escola' => [
                        'id' => intval($escola_id),
                        'nome' => $escola_post ? $escola_post->post_title : null,
                        'imagem_logo_escola' => $imagem_logo_escola,
                        'l1_escolha_data_inicio' => $l1_escolha_data_inicio_escola,
                        'l1_escolha_data_fim' => $l1_escolha_data_fim_escola,
                        'l1_entrega_data' => $l1_entrega_data_escola,
                        'l2_escolha_data_inicio' => $l2_escolha_data_inicio_escola,
                        'l2_escolha_data_fim' => $l2_escolha_data_fim_escola,
                        'l2_entrega_data' => $l2_entrega_data_escola
                    ],
                    'unidade' => [
                        'id' => intval($unidade_id),
                        'nome' => $unidade_post ? $unidade_post->post_title : null
                    ],
                    'turma' => [
                        'id' => intval($turma_id),
                        'nome' => $turma_post ? $turma_post->post_title : null
                    ]
                ];
            }
            wp_send_json_success($dados);
        } else {
            wp_send_json_error(['message' => 'Participante não encontrado.']);
        }
    }
    
}

Login::getInstance();
