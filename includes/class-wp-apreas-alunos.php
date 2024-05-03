<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

class Alunos {
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'init', [$this, 'alunos_posttype'], );
        add_action( 'admin_init', [$this,'adicionar_capacidades_para_administrador']);
        add_action( 'add_meta_boxes', [$this,'adicionar_meta_box_alunos']);
        add_action( 'save_post', [$this,'salvar_meta_box_alunos']);

    }

    // Função para adicionar o meta box
    function adicionar_meta_box_alunos() {
        add_meta_box(
            'meta-box-alunos',
            'Informações do Aluno', 
            [$this,'exibir_meta_box_alunos'], 
            'alunos', 
            'normal', 
            'high' 
        );
    }

    // Callback para exibir o conteúdo do meta box
    function exibir_meta_box_alunos($post) {

        // Recupere os valores dos campos personalizados, se existirem
        $nome = get_post_meta($post->ID, 'nome', true);
        $ultimo_nome = get_post_meta($post->ID, 'ultimo_nome', true);

        ?>
        <div class="row mt-4 mb-4">
            <div class="col">
                <div class="form-group">
                    <label for="nome" class="mb-1 fw-bold">Nome</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?php echo esc_attr($nome); ?>" />
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="ultimo_nome" class="mb-1 fw-bold">Último Nome</label>
                    <input type="text" id="ultimo_nome" name="ultimo_nome" class="form-control" value="<?php echo esc_attr($ultimo_nome); ?>" />
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="senha_nome" class="mb-1 fw-bold">Senha Nome</label>
                    <input type="text" id="senha_nome" name="senha_nome" class="form-control" value="<?php ?>" />
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="data_nascimento" class="mb-1 fw-bold">Data de Nascimento</label>
                    <input type="text" id="data_nascimento" name="data_nascimento" class="form-control" value="<?php ?>" />
                </div>
            </div>
        </div>
        <?php
    }

    // Salvar os valores do meta box
    function salvar_meta_box_alunos($post_id) {
        if (isset($_POST['nome'])) {
            update_post_meta($post_id, 'nome', sanitize_text_field($_POST['nome']));
        }
        if (isset($_POST['idade'])) {
            update_post_meta($post_id, 'idade', sanitize_text_field($_POST['idade']));
        }
    }

    function alunos_posttype () {
        $labels = array(
            'name'                => 'Alunos',
            'singular_name'       => 'Alunos',
            'menu_name'           => 'Alunos',
            'all_items'           => 'Todos os Alunos',
            'view_item'           => 'Ver Alunos',
            'add_new_item'        => 'Adicionar novo Aluno',
            'add_new'             => 'Adicionar novo',
            'edit_item'           => 'Editar Alunos',
            'update_item'         => 'Atualizar Alunos',
            'search_items'        => 'Procurar Alunos',
            'not_found'           => 'Não encontrado',
            'not_found_in_trash'  => 'Não encontrado no lixo',
        );
                
        $args = array(
            'label'               => 'Alunos',
            'description'         => 'Inclua novos Alunos',
            'labels'              => $labels,
            'menu_icon'           => 'dashicons-groups',
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
            'rest_base' => 'alunos',
            'capabilities' => array(
                'edit_post'              => 'edit_alunos',
                'read_post'              => 'read_alunos',
                'delete_post'            => 'delete_alunos',
                'create_posts'           => 'create_alunos',
                'edit_posts'             => 'edit_alunos',
                'edit_others_posts'      => 'manage_alunos',
                'publish_posts'          => 'manage_alunos',
                'read_private_posts'     => 'read',
                'read'                   => 'read',
                'delete_posts'           => 'manage_alunos',
                'delete_private_posts'   => 'manage_alunos',
                'delete_published_posts' => 'manage_alunos',
                'delete_others_posts'    => 'manage_alunos',
                'edit_private_posts'     => 'edit_alunos',
                'edit_published_posts'   => 'edit_alunos'
            )
        );
                 
        register_post_type( 'alunos', $args );

        
    }

    public static function adicionar_capacidades_para_administrador() {
        $administrator_role = get_role( 'administrator' );
        // POST
        $administrator_role->add_cap('edit_alunos');
        $administrator_role->add_cap('read_alunos');
        $administrator_role->add_cap('delete_alunos');
        $administrator_role->add_cap('create_alunos');
        $administrator_role->add_cap('manage_alunos');
        $administrator_role->add_cap('edit_alunos');
        // POST
    }

}

Alunos::getInstance();
