<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

class Eventos {
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'init', [$this, 'eventos_posttype'], );
        add_action( 'admin_init', [$this,'eventos_capacidades_administrador']);
        add_action( 'add_meta_boxes', [$this,'adicionar_meta_box_eventos']);
        add_action( 'save_post', [$this,'salvar_meta_box_eventos']);

    }

    function eventos_posttype() {
        // Labels para o post type 'eventos'
        $labels = array(
            'name'                  => 'Eventos',
            'singular_name'         => 'Evento',
            'menu_name'             => 'Eventos',
            'all_items'             => 'Todos os Eventos',
            'view_item'             => 'Ver Evento',
            'add_new_item'          => 'Adicionar novo Evento',
            'add_new'               => 'Adicionar novo',
            'edit_item'             => 'Editar Evento',
            'update_item'           => 'Atualizar Evento',
            'search_items'          => 'Procurar Eventos',
            'not_found'             => 'Não encontrado',
            'not_found_in_trash'    => 'Não encontrado no lixo',
        );
    
        // Argumentos para o post type
        $args = array(
            'label'                 => 'Eventos',
            'description'           => 'Inclua novos Eventos',
            'labels'                => $labels,
            'menu_icon'             => 'dashicons-tickets-alt',
            'supports'              => array('title', 'editor', 'thumbnail'), // Adicione 'editor' e 'thumbnail' se necessário
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => true,
            'show_in_admin_bar'     => true,
            'menu_position'         => 6,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rest_base'             => 'eventos',
            'capabilities'          => array(
                'edit_post'              => 'edit_eventos',
                'read_post'              => 'read_eventos',
                'delete_post'            => 'delete_eventos',
                'create_posts'           => 'create_eventos',
                'edit_posts'             => 'edit_eventos',
                'edit_others_posts'      => 'manage_eventos',
                'publish_posts'          => 'manage_eventos',
                'read_private_posts'     => 'read',
                'read'                   => 'read',
                'delete_posts'           => 'manage_eventos',
                'delete_private_posts'   => 'manage_eventos',
                'delete_published_posts' => 'manage_eventos',
                'delete_others_posts'    => 'manage_eventos',
                'edit_private_posts'     => 'edit_eventos',
                'edit_published_posts'   => 'edit_eventos'
            )
        );
    
        // Registra o post type
        register_post_type('eventos', $args);
    
        // Labels para a taxonomia 'categorias_eventos'
        $categoria_labels = array(
            'name'              => __( 'Categorias', 'text_domain' ),
            'singular_name'     => __( 'Categoria', 'text_domain' ),
            'search_items'      => __( 'Buscar Categorias', 'text_domain' ),
            'all_items'         => __( 'Todas as Categorias', 'text_domain' ),
            'parent_item'       => __( 'Categoria pai', 'text_domain' ),
            'parent_item_colon' => __( 'Categoria pai:', 'text_domain' ),
            'edit_item'         => __( 'Editar Categoria', 'text_domain' ),
            'update_item'       => __( 'Atualizar Categoria', 'text_domain' ),
            'add_new_item'      => __( 'Adicionar nova Categoria', 'text_domain' ),
            'new_item_name'     => __( 'Novo nome da Categoria', 'text_domain' ),
            'menu_name'         => __( 'Categorias', 'text_domain' ),
        );
    
        // Registra a taxonomia
        register_taxonomy('categorias_eventos', array('eventos'), array(
            'hierarchical'      => true,
            'labels'            => $categoria_labels,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_in_nav_menus' => false,
            'meta_box_cb'       => false,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'categorias_eventos'),
            'capabilities'      => array(
                'manage_terms' => 'manage_terms_categorias_eventos',
                'edit_terms'   => 'edit_terms_categorias_eventos',
                'delete_terms' => 'delete_terms_categorias_eventos',
                'assign_terms' => 'assign_terms_categorias_eventos'
            )
        ));
    }
    
  
    

    function adicionar_meta_box_eventos() {
        add_meta_box(
            'meta-box-eventos',
            'Informações do Evento', 
            [$this,'exibir_meta_box_eventos'], 
            'eventos', 
            'normal', 
            'high' 
        );
    }

    // Callback para exibir o conteúdo do meta box
    function exibir_meta_box_eventos($post) {
        $nome = get_post_meta($post->ID, 'nome', true);
        ?>
        <script>
            jQuery(document).ready(function($) {
                $('.categorias_eventos').select2({
                    language: "pt-BR"
                });
                // CHECKBOX
                $("#checkbox_categorias_eventos").click(function(){
                    if($("#checkbox_categorias_eventos").is(':checked') ){ 
                        $(".categorias_eventos").find('option').prop("selected",true);
                        $(".categorias_eventos").trigger('change');
                    } else { 
                        $(".categorias_eventos").find('option').prop("selected",false);
                        $(".categorias_eventos").trigger('change');
                    }
                });
                // CHECKBOX 
            })
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
        <style>
            .post-type-eventos span.select2.select2-container {
                width: 100% !important;
                margin-bottom: 13px !important;
            }
            .post-type-eventos .select2-container--default .select2-selection--multiple .select2-selection__rendered li{
                font-size: 13px !important;
                line-height: 1.5em !important;
                padding: 0px 8px 4px !important;
                font-weight: 500 !important;
                text-transform: uppercase !important;
                color: #5a5a5a !important;
            }
            .post-type-eventos input.select2-search__field {
                min-height: 26px !important;
            }
            .post-type-eventos .select2-container--default .select2-selection--multiple {
                padding-bottom: 0 !important;
            }
            .post-type-eventos .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
                color: #b61111 !important;
            }
            .post-type-eventos .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
                border-right: none !important;
                padding: 0 !important;
                padding-right: 3px !important;
                background: none !important;
            } 
            .post-type-eventos .select2-container--default.select2-container--focus .select2-selection--multiple {
                border: solid #5897fb 1px !important;
                outline: 0 !important;
            }
            .post-type-eventos span.select2-results ul li { 
                font-size: 14px !important; text-transform: uppercase !important; 
            }
        </style>
        <?php
        // CATEGORIAS & TERMOS
            $categorias_eventos = get_post_field( 'categorias_eventos', $post->ID );
            wp_set_object_terms( $post->ID, null, 'categorias_eventos' ); 
            if ($categorias_eventos != '' || $categorias_eventos != null) {
                wp_set_object_terms($post->ID, $categorias_eventos, 'categorias_eventos', true);
            }
            $categorias = [];
            $terms_categorias = get_terms([
                'taxonomy' => 'categorias_eventos',
                'hide_empty' => false,
            ]);

            foreach ($terms_categorias as $term){
                $combinado = $term->name.'#'.$term->slug; 
                array_push($categorias, $combinado);
            }
            $resultado_categorias = array_unique($categorias);
            $contar_categorias = count($categorias);
        // CATEGORIAS & TERMOS
        ?>
        <!-- CATEGORIAS -->
        <div class="col-xxl" style="padding-top: 20px;">
        <span class="" style="width: 70px; display: inline-block; padding-right: 20px; font-weight:600; color:#000;"><label> <h6 class="mb-0"> CATEGORIAS </h6> </label> </span> 
            <div class="selecionar-todos" style="padding-top:11px; padding-bottom: 15px; display: block; width: 100%;" > 
                <input type="checkbox" id="checkbox_categorias_eventos" > Selecionar Todos(as)
            </div>
            <select id="categorias_eventos" class="categorias_eventos" name="categorias_eventos[]" multiple="multiple">
            <?php for ($i = 0; $i < $contar_categorias; $i++) { 
                $separacao = explode("#", $resultado_categorias[$i]);
                ?>
                <option <?php if(!empty($categorias_eventos) && in_array( $separacao[1], $categorias_eventos) ){ echo "selected='selected'";} ?> value="<?php echo $separacao[1]; ?>"><?php echo $separacao[0]; ?></option>
            <?php } ?>
            </select>
            <div class="invalid-feedback" id="categoriasError"></div>
        </div>
        <!-- CATEGORIAS -->
        <div class="row mt-3 mb-4">
            <div class="col">
                <div class="form-group">
                    <span class="" style="width: 70px; display: inline-block; padding-right: 20px; font-weight:600; color:#000;"><label> <h6> NOME </h6> </label> </span> 
                    <input type="text" id="nome" name="nome" class="form-control" value="<?php echo esc_attr($nome); ?>" />
                </div>
            </div>
        </div>
        <?php
    }

    // Salvar os valores do meta box
    function salvar_meta_box_eventos($post_id) {
        // CATEGORIAS
        if ( isset($_POST['categorias_eventos']) ) {
            update_post_meta( $post_id, 'categorias_eventos', $_POST['categorias_eventos'] );
        } else {
            update_post_meta( $post_id, 'categorias_eventos', '');
        }
        // CATEGORIAS
        if (isset($_POST['nome'])) {
            update_post_meta($post_id, 'nome', sanitize_text_field($_POST['nome']));
        }
    }

    public static function eventos_capacidades_administrador() {
        $administrator_role = get_role( 'administrator' );
        // POST
        $administrator_role->add_cap('edit_eventos');
        $administrator_role->add_cap('read_eventos');
        $administrator_role->add_cap('delete_eventos');
        $administrator_role->add_cap('create_eventos');
        $administrator_role->add_cap('manage_eventos');
        $administrator_role->add_cap('edit_eventos');
        // POST

        // CATEGORIAS
        $administrator_role->add_cap('manage_terms_categorias_eventos');
        $administrator_role->add_cap('edit_terms_categorias_eventos');
        $administrator_role->add_cap('delete_terms_categorias_eventos');
        $administrator_role->add_cap('assign_terms_categorias_eventos');
        // CATEGORIAS
    }
}

Eventos::getInstance();
