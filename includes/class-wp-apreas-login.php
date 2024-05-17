<?php
namespace Apreas;

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
    }

    function render_login_form() {
        ob_start(); ?>
        
        <div id="loginContainer">
            <div id="loginOverlay"></div>
            <div id="loginContent">
                <form class="row g-3" id="form_login" name="form_login" enctype="multipart/form-data">
                    <div class="col-md-6">
                        <label for="senha_nome" class="form-label"> Nome</label>
                        <input type="text" class="form-control senha_nome" name="senha_nome" id="senha_nome" placeholder="Nome">
                    </div>
                    <div class="col-md-6">
                        <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                        <input type="date" class="form-control data_nascimento" name="data_nascimento" id="data_nascimento" placeholder="Data de Nascimento">
                    </div>
                    <div class="col-12">
                        <label for="escola" class="form-label">Escola</label>
                        <select class="form-select" name="escola" aria-label="Escola">
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
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                    <div class="error"> </div>
                </form>
            </div>
        </div>
    
        <!-- CSS -->
        <style>
            #loginContainer {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
            }
    
            #loginOverlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.7); 
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
        </style>
        <!-- CSS -->
        
        <?php
        return ob_get_clean();
    }
    
    public function process_login_form() {
        $nome = $_POST['formData']['senha_nome'];
        $data_nascimento = $_POST['formData']['data_nascimento'];
        $escola = $_POST['formData']['escola'];
        if (empty($nome)) {
            wp_send_json_error('Digite o nome do Aluno.');
        }
        elseif (empty($data_nascimento)) {
            wp_send_json_error('Digite a Data de Nascimento.');
        }
        elseif (empty($escola)) {
            wp_send_json_error('Digite a Escola.');
        }
        else {
            wp_send_json_success('Acesso Permitido');
        }
    }
    
 
}

Login::getInstance();