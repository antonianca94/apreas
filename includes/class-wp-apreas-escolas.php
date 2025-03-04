<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

class Escolas {
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'init', [$this, 'escolas_posttype'], );
        add_action( 'admin_init', [$this,'escolas_capacidades_administrador']);
        add_action( 'add_meta_boxes', [$this,'adicionar_meta_box_escolas']);
        add_action( 'save_post', [$this,'salvar_meta_box_escolas']);
        add_action('admin_enqueue_scripts', [$this,'enqueue_flatpickr_assets']);

    }

    function escolas_posttype () {
        $labels = array(
            'name'                => 'Escolas',
            'singular_name'       => 'Escola',
            'menu_name'           => 'Escolas',
            'all_items'           => 'Todos os Escolas',
            'view_item'           => 'Ver Escolas',
            'add_new_item'        => 'Adicionar nova Escola',
            'add_new'             => 'Adicionar nova',
            'edit_item'           => 'Editar Escolas',
            'update_item'         => 'Atualizar Escolas',
            'search_items'        => 'Procurar Escolas',
            'not_found'           => 'Não encontrado',
            'not_found_in_trash'  => 'Não encontrado no lixo',
        );
                
        $args = array(
            'label'               => 'Escolas',
            'description'         => 'Inclua novas Escolas',
            'labels'              => $labels,
            'menu_icon'           => 'dashicons-building',
            'supports'            => array('title','content'), 
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 6,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest'       => true,
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rest_base' => 'escolas',
            'capabilities' => array(
                'edit_post'              => 'edit_escolas',
                'read_post'              => 'read_escolas',
                'delete_post'            => 'delete_escolas',
                'create_posts'           => 'create_escolas',
                'edit_posts'             => 'edit_escolas',
                'edit_others_posts'      => 'manage_escolas',
                'publish_posts'          => 'manage_escolas',
                'read_private_posts'     => 'read',
                'read'                   => 'read',
                'delete_posts'           => 'manage_escolas',
                'delete_private_posts'   => 'manage_escolas',
                'delete_published_posts' => 'manage_escolas',
                'delete_others_posts'    => 'manage_escolas',
                'edit_private_posts'     => 'edit_escolas',
                'edit_published_posts'   => 'edit_escolas'
            )
        );
                 
        register_post_type( 'escolas', $args );

        
    }

    function enqueue_flatpickr_assets() {
        wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
        wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true);
        wp_enqueue_script('flatpickr-locale-pt', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js', array('flatpickr-js'), null, true);
        
    }
    
    function adicionar_meta_box_escolas() {
        add_meta_box(
            'meta-box-escolas',
            'Informações da Escola', 
            [$this,'exibir_meta_box_escolas'], 
            'escolas', 
            'normal', 
            'high' 
        );
        add_meta_box(
            'meta-box-lote-one',
            'LOTE 1', 
            [$this,'exibir_lote_one'], 
            'escolas', 
            'normal', 
            'high' 
        );
    }

    function exibir_lote_one($post) {
        // ESCOLHA
        $escolha_data_inicio = get_post_meta($post->ID, 'escolha_data_inicio', true);
        $escolha_data_fim = get_post_meta($post->ID, 'escolha_data_fim', true);
        ?>
        <div class="row mt-4 mb-4">
            <label for="nome" class="mb-4 fw-bold" style="font-size: 1rem; color:#7A7A7A;">ESCOLHA</label>
            <div class="col">
                <div class="form-group">
                    <label for="escolha_data_inicio" class="mb-2 fw-bold">Data de Início</label>
                    <div class="input-group">
                        <input type="text" id="escolha_data_inicio" name="escolha_data_inicio" class="form-control" value="<?php echo esc_attr($escolha_data_inicio); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="escolha_data_fim" class="mb-2 fw-bold">Data de Fim</label>
                    <div class="input-group">
                        <input type="text" id="escolha_data_fim" name="escolha_data_fim" class="form-control" value="<?php echo esc_attr($escolha_data_fim); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#escolha_data_inicio').flatpickr({
                    enableTime: true,
                    dateFormat: "d/m/Y H:i",
                    time_24hr: true,
                    locale: 'pt'
                });
                $('#escolha_data_fim').flatpickr({
                    enableTime: true,
                    dateFormat: "d/m/Y H:i",
                    time_24hr: true,
                    locale: 'pt'
                });
            });
        </script>
        
        <?php
        // ESCOLHA 

        // ENTREGA
        $entrega_data_inicio = get_post_meta($post->ID, 'entrega_data_inicio', true);
        $entrega_data_fim = get_post_meta($post->ID, 'entrega_data_fim', true);
        ?>
        
        <hr class="my-1"/>

        <div class="row mt-4 mb-4">
            <label for="nome" class="mb-4 fw-bold" style="font-size: 1rem; color:#7A7A7A;">ENTREGA</label>
            <div class="col">
                <div class="form-group">
                    <label for="entrega_data_inicio" class="mb-2 fw-bold">Data de Início</label>
                    <div class="input-group">
                        <input type="text" id="entrega_data_inicio" name="entrega_data_inicio" class="form-control" value="<?php echo esc_attr($entrega_data_inicio); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="entrega_data_fim" class="mb-2 fw-bold">Data de Fim</label>
                    <div class="input-group">
                        <input type="text" id="entrega_data_fim" name="entrega_data_fim" class="form-control" value="<?php echo esc_attr($entrega_data_fim); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#entrega_data_inicio').flatpickr({
                    enableTime: true,
                    dateFormat: "d/m/Y H:i",
                    time_24hr: true,
                    locale: 'pt'
                });
                $('#entrega_data_fim').flatpickr({
                    enableTime: true,
                    dateFormat: "d/m/Y H:i",
                    time_24hr: true,
                    locale: 'pt'
                });
            });
        </script>
        <?php
        // ENTREGA
    }

    // Callback para exibir o conteúdo do meta box
    function exibir_meta_box_escolas($post) {
        $nome = get_post_meta($post->ID, 'nome', true);
        $imagem_logo_escola = get_post_meta($post->ID, 'imagem_logo_escola', true);
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var titleInput = document.getElementById('title');
                var nomeInput = document.getElementById('nome');
                var titleLabel = document.getElementById('title-prompt-text');
                titleInput.addEventListener('input', function() {
                    nomeInput.value = titleInput.value;
                    if (titleInput.value === '') {
                        titleLabel.style.display = 'block';
                    } else {
                        titleLabel.style.display = 'none';
                    }
                });
                nomeInput.addEventListener('input', function() {
                    titleInput.value = nomeInput.value;
                    if (nomeInput.value === '') {
                        titleLabel.style.display = 'block';
                    } else {
                        titleLabel.style.display = 'none';
                    }
                });
            });
        </script>
        <div class="row mt-4 mb-4">
            <div class="col">
                <div class="form-group">
                    <label for="nome" class="mb-1 fw-bold">Nome</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?php echo esc_attr($nome); ?>" />
                </div>
            </div>
        </div>

        <!-- LOGO ESCOLA -->
        <div class="row mb-4">
            <div class="col-xxl mt-2">
                <label class="mb-2 fw-bold">Logo Escola</label>
                <div class="corpo-upload" style="width:100%; margin-bottom:10px;"><a href="#" id="imagem_logo_escola_upload" name="imagem_logo_escola_upload" class="imagem_logo_escola_btn button button-secondary"><span class="dashicons dashicons-cloud-upload"></span> Carregar Imagem</a></div>
                <div style="width:100%;">
                    <input type="text" id="imagem_logo_escola" name="imagem_logo_escola" class="imagem_logo_escola" value="<?php  echo $imagem_logo_escola; ?>" />
                </div>
                <div class="preview-logo">
                    <div class="preview-logo-escola">
                    </div>
                </div>
            </div>
        </div>
        <!-- LOGO ESCOLA -->

        <?php
    }

    // Salvar os valores do meta box
    function salvar_meta_box_escolas($post_id) {
        if (isset($_POST['nome'])) {
            update_post_meta($post_id, 'nome', sanitize_text_field($_POST['nome']));
        }
        if (isset($_POST['imagem_logo_escola'])) {
            update_post_meta($post_id, 'imagem_logo_escola', $_POST['imagem_logo_escola'] );
        }

        if (isset($_POST['escolha_data_inicio'])) {
            update_post_meta($post_id, 'escolha_data_inicio', sanitize_text_field($_POST['escolha_data_inicio']));
        }
        if (isset($_POST['escolha_data_fim'])) {
            update_post_meta($post_id, 'escolha_data_fim', sanitize_text_field($_POST['escolha_data_fim']));
        }
        if (isset($_POST['entrega_data_inicio'])) {
            update_post_meta($post_id, 'entrega_data_inicio', sanitize_text_field($_POST['entrega_data_inicio']));
        }
        if (isset($_POST['entrega_data_fim'])) {
            update_post_meta($post_id, 'entrega_data_fim', sanitize_text_field($_POST['entrega_data_fim']));
        }

    }

    public static function escolas_capacidades_administrador() {
        $administrator_role = get_role( 'administrator' );
        // POST
        $administrator_role->add_cap('edit_escolas');
        $administrator_role->add_cap('read_escolas');
        $administrator_role->add_cap('delete_escolas');
        $administrator_role->add_cap('create_escolas');
        $administrator_role->add_cap('manage_escolas');
        $administrator_role->add_cap('edit_escolas');
        // POST
    }
}

Escolas::getInstance();
