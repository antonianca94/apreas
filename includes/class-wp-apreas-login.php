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
    }

    function render_login_form() {
        ob_start(); ?>
        
        <form class="row g-3">
            <div class="col-md-6">
                <label for="senha_nome" class="form-label"> Nome</label>
                <input type="password" class="form-control senha_nome" id="senha_nome" placeholder="Nome">
            </div>
            <div class="col-md-6">
                <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                <input type="date" class="form-control data_nascimento" id="data_nascimento" placeholder="Data de Nascimento">
            </div>
            <div class="col-12">
                <label for="escola" class="form-label">Escola</label>
                <input type="text" class="form-control escola" id="escola" placeholder="Escola">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Enviar</button>
            </div>
        </form>
        
        <?php
        return ob_get_clean();
    }

}

Login::getInstance();
