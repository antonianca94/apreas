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

    }

    function escolas_posttype () {
        $labels = array(
            'name'                => 'Escolas',
            'singular_name'       => 'Escola',
            'menu_name'           => 'Escolas',
            'all_items'           => 'Todos os Escolas',
            'view_item'           => 'Ver Escolas',
            'add_new_item'        => 'Adicionar novo Aluno',
            'add_new'             => 'Adicionar novo',
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

    function adicionar_meta_box_escolas() {
        add_meta_box(
            'meta-box-escolas',
            'Informações da Escola', 
            [$this,'exibir_meta_box_escolas'], 
            'escolas', 
            'normal', 
            'high' 
        );
    }

    // Callback para exibir o conteúdo do meta box
    function exibir_meta_box_escolas($post) {
        $nome = get_post_meta($post->ID, 'nome', true);
        ?>
        <div class="row mt-4 mb-4">
            <div class="col">
                <div class="form-group">
                    <label for="nome" class="mb-1 fw-bold">Nome</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?php echo esc_attr($nome); ?>" />
                </div>
            </div>
        </div>
        <?php
    }

    // Salvar os valores do meta box
    function salvar_meta_box_escolas($post_id) {
        if (isset($_POST['nome'])) {
            update_post_meta($post_id, 'nome', sanitize_text_field($_POST['nome']));
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
