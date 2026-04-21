<?php
/*
Plugin Name: Apreas WP Plugin
Plugin URI: https://apreas.com.br/
Description: Recursos extras para os Alunos.
Version: 2.1.1
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
        add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_assets' ) ); 

    }

    public function plugin_loaded() {
        $Login = \Apreas\Login::getInstance();
        // Escolas precisa ser instanciada sempre (frontend + admin) para registrar o shortcode
        $Escolas = \Apreas\Escolas::getInstance();
        // Campos extras do checkout WooCommerce (substitui plugin externo)
        $Checkout = \Apreas\Checkout::getInstance();

        if (is_admin()) {
            // INSTANCIAS
            $Alunos = \Apreas\Alunos::getInstance();
            $Unidades = \Apreas\Unidades::getInstance();
            $Turmas = \Apreas\Turmas::getInstance();

            $Participantes = \Apreas\Participantes::getInstance();
            $Eventos = \Apreas\Eventos::getInstance();
            // INSTANCIAS






//COLUNA ALUNOS
add_filter('manage_alunos_posts_columns', function($columns) {

    $new_columns = [
        'cb'      => $columns['cb'],    // Checkbox de seleção
        'title'   => 'Aluno',           // Nome do Aluno
        'escola'  => 'Escola',
        'turma'   => 'Turma',
        'unidade' => 'Unidade',
        'data_nascimento' => 'Data de Nascimento',
        'date'    => 'Data'             // <--- Adicione esta linha aqui
    ];

    return $new_columns;
});

add_action('manage_alunos_posts_custom_column', function($column, $post_id) {
    switch ($column) {
        case 'escola':
            // Buscamos o ID da escola que está associado a este aluno
            $escola_id = get_post_meta($post_id, 'escola', true); 
            if ($escola_id) {
                echo get_the_title($escola_id);
            } else {
                echo '—';
            }
            break;

        case 'turma':
            $turma_id = get_post_meta($post_id, 'turma', true);
            if ($turma_id) {
                echo get_the_title($turma_id);
            } else {
                echo '—';
            }
            break;

        case 'unidade':
            $unidade_id = get_post_meta($post_id, 'unidade', true);
            if ($unidade_id) {
                echo get_the_title($unidade_id);
            } else {
                echo '—';
            }
            break;
        case 'data_nascimento':
$data = get_post_meta($post_id, 'data_nascimento', true); // Verifique se é 'data_nascimento' ou 'data_ascimento'
    
    if ($data) {
        // Se a data vier do banco como 2026-04-20, isso transforma em 20/04/2026
        echo date('d/m/Y', strtotime($data));
    } else {
        echo '—';
    }
            break;
    }
}, 10, 2);

add_filter('manage_edit-alunos_sortable_columns', function($sortable_columns) {
    $sortable_columns['escola']          = 'escola';
    $sortable_columns['turma']           = 'turma';
    $sortable_columns['unidade']         = 'unidade';
    $sortable_columns['data_nascimento'] = 'data_nascimento';
    return $sortable_columns;
});

add_action('pre_get_posts', function($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');

    switch ($orderby) {
        case 'escola':
        case 'turma':
        case 'unidade':
            $query->set('meta_key', $orderby); // Usa o slug da coluna como chave do meta_data
            $query->set('orderby', 'meta_value_num'); // Ordena como número (já que guarda o ID)
            break;

        case 'data_nascimento':
            $query->set('meta_key', 'data_nascimento');
            $query->set('orderby', 'meta_value'); 
            // Se a data estiver no formato YYYY-MM-DD, a ordenação de texto funciona perfeitamente.
            break;
    }
});




add_action('restrict_manage_posts', function($post_type) {
    if ($post_type !== 'alunos') {
        return;
    }

    // --- FILTRO DE ESCOLA ---
    $escolas = get_posts([
        'post_type'      => 'escolas', // Verifique se o slug do CPT de escolas é 'escola'
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC'
    ]);

    $escola_sel = isset($_GET['filtro_escola']) ? $_GET['filtro_escola'] : '';

    echo '<select name="filtro_escola">';
    echo '<option value="">Todas as Escolas</option>';
    foreach ($escolas as $esc) {
        printf('<option value="%s" %s>%s</option>', $esc->ID, selected($escola_sel, $esc->ID, false), $esc->post_title);
    }
    echo '</select>';

    // --- FILTRO DE TURMA ---
    $turmas = get_posts([
        'post_type'      => 'turmas', // Verifique se o slug do CPT de turmas é 'turma'
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC'
    ]);

    $turma_sel = isset($_GET['filtro_turma']) ? $_GET['filtro_turma'] : '';

    echo '<select name="filtro_turma">';
    echo '<option value="">Todas as Turmas</option>';
    foreach ($turmas as $tur) {
        printf('<option value="%s" %s>%s</option>', $tur->ID, selected($turma_sel, $tur->ID, false), $tur->post_title);
    }
    echo '</select>';
});


add_action('pre_get_posts', function($query) {
    global $pagenow;

    if (!is_admin() || $pagenow !== 'edit.php' || $query->get('post_type') !== 'alunos' || !$query->is_main_query()) {
        return;
    }

    $meta_query = [];

    // Se selecionou Escola
    if (!empty($_GET['filtro_escola'])) {
        $meta_query[] = [
            'key'     => 'escola', // Nome da meta_key que você usa para salvar o ID da escola
            'value'   => $_GET['filtro_escola'],
            'compare' => '='
        ];
    }

    // Se selecionou Turma
    if (!empty($_GET['filtro_turma'])) {
        $meta_query[] = [
            'key'     => 'turma', // Nome da meta_key que você usa para salvar o ID da turma
            'value'   => $_GET['filtro_turma'],
            'compare' => '='
        ];
    }

    // Se houver algum filtro ativo, aplica na consulta
    if (count($meta_query) > 0) {
        if (count($meta_query) > 1) {
            $meta_query['relation'] = 'AND';
        }
        $query->set('meta_query', $meta_query);
    }
});

//FIM COLUNA ALUNOS






        } 

    }


    public function load_frontend_assets() {
        $this->enqueue_frontend_styles();
        $this->enqueue_frontend_scripts();        
    }

    public function load_admin_assets() {
        $this->enqueue_admin_styles();
        $this->enqueue_admin_scripts();        
    }

    private function enqueue_frontend_styles() {
        wp_enqueue_style('Participantes_CSS', plugins_url('/admin/css/participantes.css', __FILE__), array(), '1.0.33');
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');
        wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', [], null);
        wp_enqueue_style('bootstrap-icons', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css', array(), '1.5.0');

        // Fix: Tabela de revisão do pedido WooCommerce não aplicava largura 100%
        $checkout_css = "
            .elementor-jet-checkout-order-review,
            .woocommerce-checkout-review-order,
            #order_review,
            .woocommerce-checkout-review-order-table,
            table.shop_table.woocommerce-checkout-review-order-table {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            h3#order_review_heading {
                text-align: left !important;
                width: 100% !important;
                margin-top: 4rem !important;
            }
        ";
        wp_add_inline_style('Participantes_CSS', $checkout_css);

    }

    private function enqueue_frontend_scripts() {
        wp_enqueue_script('jquery');

        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.3.0', true);
        wp_enqueue_script('Login_JS', plugins_url('/admin/js/login.js', __FILE__), array(), '1.0.77', true);
        wp_enqueue_script('sweetalert2-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
        wp_enqueue_script('Validation_JS', plugins_url('/admin/js/validation.js', __FILE__), array(), '1.0.4', true);

    }

    private function enqueue_admin_styles() {

    }

    private function enqueue_admin_scripts() {

        if ( ! did_action( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }
        // wp_enqueue_script('Login_JS', plugins_url('/admin/js/login.js', __FILE__), array(), '1.0.4', true);
        wp_enqueue_script('Upload_JS', plugins_url('/admin/js/upload.js', __FILE__), array(), '1.0.4', true);
        wp_enqueue_script('Preview_JS', plugins_url('/admin/js/preview.js', __FILE__), array(), '1.0.3', true);
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css', array(), '4.1.0');
        wp_enqueue_script('pt-BR', plugins_url('/admin/js/pt-BR.js', __FILE__), array('select2'), '1.0.0', true);

        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.3.0', true);
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');
        wp_enqueue_style('Style_CSS', plugins_url('/admin/css/style.css', __FILE__), array(), '1.0.12');

    }
    
}

APREAS_Plugin::get_instance();
