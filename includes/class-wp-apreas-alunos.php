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

        $nome = get_post_meta($post->ID, 'nome', true);
        $ultimo_nome = get_post_meta($post->ID, 'ultimo_nome', true);
        $senha_nome = get_post_meta($post->ID, 'senha_nome', true);
        $data_nascimento = get_post_meta($post->ID, 'data_nascimento', true);
        $escola = get_post_meta($post->ID, 'escola', true);
        $escola = !empty($escola) ? explode(',', $escola) : [];

        $unidade = get_post_meta($post->ID, 'unidade', true);
        $unidade = !empty($unidade) ? explode(',', $unidade) : [];

        $turma = get_post_meta($post->ID, 'turma', true);
        $turma = !empty($turma) ? explode(',', $turma) : [];
        // var_dump('SENHA_NOME: '.$senha_nome);
        // var_dump('DATA_NASCIMENTO: '.$data_nascimento);
        // var_dump($escola);

        $imagem_upload_individual = get_post_meta($post->ID, 'imagem_upload_individual', true);
        $imagem_upload_individual2 = get_post_meta($post->ID, 'imagem_upload_individual2', true);
        $imagem_upload_turma = get_post_meta($post->ID, 'imagem_upload_turma', true);

        // ESCOLAS
        $args = array(
            'post_type' => 'escolas',
            'posts_per_page' => -1, 
        );
        $escolas_query = new \WP_Query($args);
        // ESCOLAS

        // UNIDADES
        $args_unidades = array(
            'post_type' => 'unidades',
            'posts_per_page' => -1, 
        );
        $unidades_query = new \WP_Query($args_unidades);
        // UNIDADES

        // TURMAS
        $args_turmas = array(
            'post_type' => 'turmas',
            'posts_per_page' => -1, 
        );
        $turmas_query = new \WP_Query($args_turmas);
        // TURMAS
        ?>
        <style>
            .post-type-alunos span.select2.select2-container {
                width: 100% !important;
                margin-bottom: 13px;
            }
            .post-type-alunos .select2-container--default .select2-selection--multiple .select2-selection__rendered li{
                font-size: 13px !important;
                line-height: 1.5em !important;
                padding: 0px 8px 4px !important;
                font-weight: 500 !important;
                text-transform: uppercase !important;
                color: #5a5a5a !important;
            }
            .post-type-alunos input.select2-search__field {
                min-height: 26px !important;
            }
            .post-type-alunos .select2-container--default .select2-selection--multiple {
                padding-bottom: 0 !important;
            }
            .post-type-alunos .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
                color: #b61111 !important;
            }
            .post-type-alunos .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
                border-right: none;
                padding: 0;
                padding-right: 3px;
                background: none;
            } 
            .post-type-alunos .select2-container--default.select2-container--focus .select2-selection--multiple {
                border: solid #5897fb 1px !important;
                outline: 0;
            }
            .post-type-alunos span.select2-results ul li { 
                font-size: 14px !important; text-transform: uppercase !important; 
            }

        </style>
        <script>

            jQuery(document).ready(function($) {
                $('.select2').select2({
                    maximumSelectionLength: 1
                });
                function removeDiacritics(str) {
                    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                }
                function updateSenhaNome() {
                    var nome = removeDiacritics($('#nome').val().toLowerCase());
                    var ultimoNome = removeDiacritics($('#ultimo_nome').val().toLowerCase()).replace(/ç/g, 'c');
                    var senhaNome = nome + '_' + ultimoNome;
                    $('#senha_nome').val(senhaNome);
                }
                function splitName(title) {
                    var nameParts = title.trim().split(' ');
                    if (nameParts.length > 1) {
                        var nome = nameParts[0];
                        var ultimoNome = nameParts[nameParts.length - 1];
                        $('#nome').val(nome);
                        $('#ultimo_nome').val(ultimoNome);
                        updateSenhaNome();
                    }
                }
                if ($('#nome').val() && $('#ultimo_nome').val()) {
                    updateSenhaNome();
                }
                $('#nome, #ultimo_nome').on('input', updateSenhaNome);
                $('#title').on('input', function() {
                    splitName($(this).val());
                });
                if ($('#title').val()) {
                    splitName($('#title').val());
                }
            });
        </script>
        <div class="row mt-4 mb-4">
            <div class="col">
                <div class="form-group">
                    <label for="nome" class="mb-2 fw-bold">Nome</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?php echo esc_attr($nome); ?>" />
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="ultimo_nome" class="mb-2 fw-bold">Último Nome</label>
                    <input type="text" id="ultimo_nome" name="ultimo_nome" class="form-control" value="<?php echo esc_attr($ultimo_nome); ?>" />
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="senha_nome" class="mb-2 fw-bold">Senha Nome</label>
                    <input type="text" id="senha_nome" name="senha_nome" class="form-control" value="<?php echo esc_attr($senha_nome); ?>" />
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="data_nascimento" class="mb-2 fw-bold">Data de Nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" value="<?php echo esc_attr($data_nascimento); ?>" />
                </div>
            </div>
        </div>
        <!-- ESCOLAS -->
        <div class="row mt-4">
            <div class="col">
                <div class="form-group">
                    <label for="escola" class="mb-2 fw-bold">Escola</label>
                    <select id="escola" name="escola" class="form-control select2" multiple="multiple">
                        <?php while ($escolas_query->have_posts()) : $escolas_query->the_post(); ?>
                            <?php
                            $selected = is_array($escola) && in_array(get_the_ID(), $escola) ? 'selected' : '';
                            ?>
                            <option value="<?php echo esc_attr(get_the_ID()); ?>" <?php echo $selected; ?>><?php the_title(); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>
        <!-- ESCOLAS -->
        <!-- UNIDADES -->
        <div class="row mt-3">
            <div class="col">
                <div class="form-group">
                    <label for="unidade" class="mb-2 fw-bold">Unidade</label>
                    <select id="unidade" name="unidade" class="form-control select2" multiple="multiple">
                        <?php while ($unidades_query->have_posts()) : $unidades_query->the_post(); ?>
                            <?php
                            $selected = is_array($unidade) && in_array(get_the_ID(), $unidade) ? 'selected' : '';
                            ?>
                            <option value="<?php echo esc_attr(get_the_ID()); ?>" <?php echo $selected; ?>><?php the_title(); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>
        <!-- UNIDADES -->
        <!-- TURMAS -->
        <div class="row mt-3">
            <div class="col">
                <div class="form-group">
                    <label for="turma" class="mb-2 fw-bold">Turma</label>
                    <select id="turma" name="turma" class="form-control select2" multiple="multiple">
                        <?php while ($turmas_query->have_posts()) : $turmas_query->the_post(); ?>
                            <?php
                            $selected = is_array($turma) && in_array(get_the_ID(), $turma) ? 'selected' : '';
                            ?>
                            <option value="<?php echo esc_attr(get_the_ID()); ?>" <?php echo $selected; ?>><?php the_title(); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>
        <!-- TURMAS -->
        <!-- IMAGEM -->
        <div class="row mb-4">
            <div class="col-xxl-6 mt-3">
                <label class="mb-2 fw-bold">Imagem Individual</label>
                <div class="corpo-upload" style="width:100%; margin-bottom:10px;"><a href="#" id="imagem_upload" name="imagem_upload" class="imagem_upload_individual_btn button button-secondary"><span class="dashicons dashicons-cloud-upload"></span> Carregar Imagem</a></div>
                <div style="width:100%;">
                    <input type="text" id="imagem_upload_individual" name="imagem_upload_individual" class="imagem_upload_individual" value="<?php  echo $imagem_upload_individual; ?>" />
                </div>
                <div class="preview-aluno">
                    <div class="preview-aluno-individual">

                    </div>
                </div>
            </div>
            <div class="col-xxl-6 mt-3">
                <label class="mb-2 fw-bold">Imagem Individual 2</label>
                <div class="corpo-upload" style="width:100%; margin-bottom:10px;"><a href="#" id="imagem_upload" name="imagem_upload" class="imagem_upload_individual_btn2 button button-secondary"><span class="dashicons dashicons-cloud-upload"></span> Carregar Imagem</a></div>
                <div style="width:100%;">
                    <input type="text" id="imagem_upload_individual2" name="imagem_upload_individual2" class="imagem_upload_individual2" value="<?php  echo $imagem_upload_individual2; ?>" />
                </div>
                <div class="preview-aluno">
                    <div class="preview-aluno-individual2">

                    </div>
                </div>
            </div>
            <div class="col-xxl-6 mt-3">
            <label class="mb-2 fw-bold">Imagem Turma</label>
                <div class="corpo-upload" style="width:100%; margin-bottom:10px;"><a href="#" id="imagem_upload" name="imagem_upload" class="imagem_upload_turma_btn button button-secondary"><span class="dashicons dashicons-cloud-upload"></span> Carregar Imagem</a></div>
                <div style="width:100%;">
                    <input type="text" id="imagem_upload_turma" name="imagem_upload_turma" class="imagem_upload_turma" value="<?php echo $imagem_upload_turma; ?>" />
                </div>
                <div class="preview-aluno">
                    <div class="preview-aluno-turma">

                    </div>
                </div>
            </div>
        </div>
        <!-- IMAGEM --> 
        <?php
    }

    // Salvar os valores do meta box
    function salvar_meta_box_alunos($post_id) {
        if (isset($_POST['nome'])) {
            update_post_meta($post_id, 'nome', sanitize_text_field($_POST['nome']));
        }
        if (isset($_POST['ultimo_nome'])) {
            update_post_meta($post_id, 'ultimo_nome', sanitize_text_field($_POST['ultimo_nome']));
        }
        if (isset($_POST['senha_nome'])) {
            update_post_meta($post_id, 'senha_nome', sanitize_text_field($_POST['senha_nome']));
        }
        if (isset($_POST['data_nascimento'])) {
            update_post_meta($post_id, 'data_nascimento', sanitize_text_field($_POST['data_nascimento']));
        }
        if (isset($_POST['escola'])) {
            update_post_meta($post_id, 'escola', $_POST['escola'] );
        }
        if (isset($_POST['unidade'])) {
            update_post_meta($post_id, 'unidade', $_POST['unidade'] );
        }
        if (isset($_POST['turma'])) {
            update_post_meta($post_id, 'turma', $_POST['turma'] );
        }
        if (isset($_POST['imagem_upload_individual'])) {
            update_post_meta($post_id, 'imagem_upload_individual', $_POST['imagem_upload_individual'] );
        }
        if (isset($_POST['imagem_upload_individual2'])) {
            update_post_meta($post_id, 'imagem_upload_individual2', $_POST['imagem_upload_individual2'] );
        }
        if (isset($_POST['imagem_upload_turma'])) {
            update_post_meta($post_id, 'imagem_upload_turma', $_POST['imagem_upload_turma'] );
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
