<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

class Unidades {
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'init', [$this, 'unidades_posttype'], );
        add_action( 'admin_init', [$this,'unidades_capacidades_administrador']);
        add_action( 'add_meta_boxes', [$this,'adicionar_meta_box_unidades']);
        add_action( 'save_post', [$this,'salvar_meta_box_unidades']);

    }

    function unidades_posttype () {
        $labels = array(
            'name'                => 'Unidades',
            'singular_name'       => 'Unidade',
            'menu_name'           => 'Unidades',
            'all_items'           => 'Todos as Unidades',
            'view_item'           => 'Ver Unidades',
            'add_new_item'        => 'Adicionar nova unidade',
            'add_new'             => 'Adicionar nova',
            'edit_item'           => 'Editar Unidades',
            'update_item'         => 'Atualizar Unidades',
            'search_items'        => 'Procurar Unidades',
            'not_found'           => 'Não encontrado',
            'not_found_in_trash'  => 'Não encontrado no lixo',
        );
                
        $args = array(
            'label'               => 'Unidades',
            'description'         => 'Inclua novas Unidades',
            'labels'              => $labels,
            'menu_icon'           => 'dashicons-admin-multisite',
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
            'rest_base' => 'unidades',
            'capabilities' => array(
                'edit_post'              => 'edit_unidades',
                'read_post'              => 'read_unidades',
                'delete_post'            => 'delete_unidades',
                'create_posts'           => 'create_unidades',
                'edit_posts'             => 'edit_unidades',
                'edit_others_posts'      => 'manage_unidades',
                'publish_posts'          => 'manage_unidades',
                'read_private_posts'     => 'read',
                'read'                   => 'read',
                'delete_posts'           => 'manage_unidades',
                'delete_private_posts'   => 'manage_unidades',
                'delete_published_posts' => 'manage_unidades',
                'delete_others_posts'    => 'manage_unidades',
                'edit_private_posts'     => 'edit_unidades',
                'edit_published_posts'   => 'edit_unidades'
            )
        );
                 
        register_post_type( 'unidades', $args );

        
    }

    function adicionar_meta_box_unidades() {
        add_meta_box(
            'meta-box-unidades',
            'Informações da unidade', 
            [$this,'exibir_meta_box_unidades'], 
            'unidades', 
            'normal', 
            'high' 
        );
    }

    // Callback para exibir o conteúdo do meta box
    function exibir_meta_box_unidades($post) {
        $nome = get_post_meta($post->ID, 'nome', true);
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
        <?php
    }

    // Salvar os valores do meta box
    function salvar_meta_box_unidades($post_id) {
        if (isset($_POST['nome'])) {
            update_post_meta($post_id, 'nome', sanitize_text_field($_POST['nome']));
        }
    }

    public static function unidades_capacidades_administrador() {
        $administrator_role = get_role( 'administrator' );
        // POST
        $administrator_role->add_cap('edit_unidades');
        $administrator_role->add_cap('read_unidades');
        $administrator_role->add_cap('delete_unidades');
        $administrator_role->add_cap('create_unidades');
        $administrator_role->add_cap('manage_unidades');
        $administrator_role->add_cap('edit_unidades');
        // POST
    }
}

Unidades::getInstance();
