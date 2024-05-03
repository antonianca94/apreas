<?php
/*
Plugin Name: Apreas WP Plugin
Plugin URI: https://apreas.com.br/
Description: Recursos extras para os Alunos.
Version: 1.0
Author: Apreas Development Team
Author URI: https://apreas.com.br/
Text Domain: apreas
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit;

spl_autoload_register(function ($class) {
    $prefix = 'Apreas\\';
    $base_dir = __DIR__ . '/includes/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-wp-apreas-' . strtolower(str_replace('\\', '-', $relative_class)) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

class APREAS_Plugin {
    private static $instance;

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        add_action( 'plugins_loaded', array( $this, 'plugin_loaded' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );
    }

    public function plugin_loaded() {

        if (is_admin()) {
            // INSTANCIAS
            $Alunos = \Apreas\Alunos::getInstance();
            $Escolas = \Apreas\Escolas::getInstance();
            // INSTANCIAS
        }
    }

    public function load_admin_assets() {
        $this->enqueue_admin_styles();
        $this->enqueue_admin_scripts();        
    }

    private function enqueue_admin_styles() {

    }

    private function enqueue_admin_scripts() {

        if ( ! did_action( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }

        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css', array(), '4.1.0');
        
        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.3.0', true);
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');

    }
    
}

APREAS_Plugin::get_instance();
