<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

class Participantes {
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'init', [$this, 'participantes_posttype'], );
        add_action( 'admin_init', [$this,'adicionar_capacidades_para_administrador']);
        add_action( 'add_meta_boxes', [$this,'adicionar_meta_box_participantes']);
        add_action( 'save_post', [$this,'salvar_meta_box_participantes']);

    }
    

    // Função para adicionar o meta box
    function adicionar_meta_box_participantes() {
        add_meta_box(
            'meta-box-participantes',
            'Informações do Participante', 
            [$this,'exibir_meta_box_participantes'], 
            'participantes', 
            'normal', 
            'high' 
        );
        add_meta_box(
            'fotos_participante',
            'Fotos do Participante',
            [$this,'render_fotos_participante_meta_box'],
            'participantes',
            'normal',
            'high'
        );
    }
    function render_fotos_participante_meta_box($post) {
        wp_nonce_field('save_fotos_participantes', 'fotos_participantes_nonce');
        $fotos = get_post_meta($post->ID, '_fotos_participantes', true) ?: array(); // Garantir que $fotos seja um array
        // Adicionar Bootstrap Icons
        ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
        <style>
            .foto-item img {
                width: 150px;  
                height: 150px;
                object-fit: cover;
                border-radius: 8px;
                box-shadow: rgba(67, 71, 85, 0.27) 0px 0px 0.25em, rgba(90, 125, 188, 0.05) 0px 0.25em 1em;
            }
            .foto-item {
                position: relative;
                text-align: center;
            }
            .foto-item .remove-foto {
                margin-top: 10px;
            }
            .truncate {
                width: 100%;             
                white-space: break-spaces;      
                overflow: hidden;         
                text-overflow: ellipsis;   
            }
        </style>
    
        <div class="mt-5">
            <div id="fotos-container" class="row">
                <?php 
                if (!empty($fotos)) {
                    foreach ($fotos as $index => $foto) {
                        $foto_caminho = isset($foto['caminho']) ? esc_url($foto['caminho']) : '';
                        $foto_nome = isset($foto['nome']) ? esc_html($foto['nome']) : 'Sem nome';
                        $foto_codigo = isset($foto['codigo']) ? esc_html($foto['codigo']) : 'Sem código';
                        ?>
                        <div class="col-md-3 mb-4 text-center">
                            <div class="foto-item mb-3">
                                <img src="<?php echo $foto_caminho; ?>" class="img-fluid">
                                <input type="hidden" name="fotos_participantes[]" value="<?php echo $foto_caminho; ?>">
                                <input type="hidden" name="nomes_participantes[]" value="<?php echo $foto_nome; ?>">
                                <input type="hidden" name="codigos_participantes[]" value="<?php echo $foto_codigo; ?>">
                                <p class="m-0 pb-2 pt-2 truncate"><b><?php echo $foto_nome; ?></b></p>
                                <p class="m-0"><?php echo $foto_codigo; ?></p>
                                <a href="#" class="btn btn-outline-danger btn-sm remove-foto">
                                    <i class="bi bi-trash"></i> Remover
                                </a>
                            </div>
                        </div>
                <?php }
                } ?>
            </div>
        </div>
    
        <div class="d-flex align-items-center justify-content-center mt-4 mb-4" style="background: whitesmoke; padding: 1rem; border-radius: 8px; border: 2px dashed #ababab;">
            <button type="button" id="add-foto" class="btn btn-secondary">
                <i class="bi bi-upload"></i> Adicionar Fotos
            </button>
        </div>
    
        <script>
            jQuery(document).ready(function($) {
                $('#add-foto').click(function() {
                    var frame = wp.media({
                        title: 'Selecionar ou Carregar Imagens',
                        button: { text: 'Usar Imagens' },
                        multiple: true
                    });
    
                    frame.on('select', function() {
                        var attachments = frame.state().get('selection').toJSON();
                        $.each(attachments, function(index, attachment) {
                            var codigoAleatorio = Math.floor(10000 + Math.random() * 90000); // Gera um código aleatório
                            $('#fotos-container').append(
                                '<div class="col-md-3 mb-4 text-center">' +
                                '<div class="foto-item">' +
                                '<img src="' + attachment.url + '" class="img-fluid">' +
                                '<input type="hidden" name="fotos_participantes[]" value="' + attachment.url + '">' +
                                '<input type="hidden" name="nomes_participantes[]" value="' + attachment.filename + '">' +
                                '<input type="hidden" name="codigos_participantes[]" value="' + codigoAleatorio + '">' +
                                '<p class="m-0 pb-2 pt-2 truncate"><b>' + attachment.filename + '</b></p>' +
                                '<p class="m-0">' + codigoAleatorio + '</p>' +
                                '<a href="#" class="btn btn-outline-danger btn-sm remove-foto">' +
                                '<i class="bi bi-trash"></i> Remover' +
                                '</a>' +
                                '</div>' +
                                '</div>'
                            );
                        });
                    });
    
                    frame.open();
                });
    
                // Remover foto
                $(document).on('click', '.remove-foto', function(e) {
                    e.preventDefault();
                    $(this).closest('.col-md-3').remove();
                });
            });
        </script>
    
        <?php
    }
    
    
    
    // Callback para exibir o conteúdo do meta box
    function exibir_meta_box_participantes($post) {

        $nome = get_post_meta($post->ID, 'nome', true);
        $ultimo_nome = get_post_meta($post->ID, 'ultimo_nome', true);
        $senha_nome = get_post_meta($post->ID, 'senha_nome', true);
        $data_nascimento = get_post_meta($post->ID, 'data_nascimento', true);
        $evento = get_post_meta($post->ID, 'evento', true);
        $evento = !empty($evento) ? explode(',', $evento) : [];
        $link_album = get_post_meta($post->ID, 'link_album', true);

        $imagem_upload_individual = get_post_meta($post->ID, 'imagem_upload_individual', true);
        $imagem_upload_individual2 = get_post_meta($post->ID, 'imagem_upload_individual2', true);
        $imagem_upload_turma = get_post_meta($post->ID, 'imagem_upload_turma', true);

        // EVENTOS
        $args = array(
            'post_type' => 'eventos',
            'posts_per_page' => -1, 
        );
        $eventos_query = new \WP_Query($args);
        // EVENTOS
        ?>

        <style>
            .post-type-participantes span.select2.select2-container {
                width: 100% !important;
                margin-bottom: 13px;
            }
            .post-type-participantes .select2-container--default .select2-selection--multiple .select2-selection__rendered li{
                font-size: 13px !important;
                line-height: 1.5em !important;
                padding: 0px 8px 4px !important;
                font-weight: 500 !important;
                text-transform: uppercase !important;
                color: #5a5a5a !important;
            }
            .post-type-participantes input.select2-search__field {
                min-height: 26px !important;
            }
            .post-type-participantes .select2-container--default .select2-selection--multiple {
                padding-bottom: 0 !important;
            }
            .post-type-participantes .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
                color: #b61111 !important;
            }
            .post-type-participantes .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
                border-right: none;
                padding: 0;
                padding-right: 3px;
                background: none;
            } 
            .post-type-participantes .select2-container--default.select2-container--focus .select2-selection--multiple {
                border: solid #5897fb 1px !important;
                outline: 0;
            }
            .post-type-participantes span.select2-results ul li { 
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

        <!-- EVENTO -->
        <div class="row mt-4">
            <div class="col">
                <div class="form-group">
                    <label for="evento" class="mb-2 fw-bold">Evento</label>
                    <select id="evento" name="evento" class="form-control select2" multiple="multiple">
                        <?php while ($eventos_query->have_posts()) : $eventos_query->the_post(); ?>
                            <?php
                            $selected = is_array($evento) && in_array(get_the_ID(), $evento) ? 'selected' : '';
                            ?>
                            <option value="<?php echo esc_attr(get_the_ID()); ?>" <?php echo $selected; ?>><?php the_title(); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>
        <!-- EVENTO -->

        <!-- LINK -->
        <div class="row mt-4">
            <div class="col">
                <div class="form-group">
                    <label for="link_album" class="mb-2 fw-bold">Link do Álbum</label>
                    <input type="text" id="link_album" name="link_album" class="form-control" value="<?php echo esc_attr($link_album); ?>" />
                </div>
            </div>
        </div>
        <!-- LINK -->

        <!-- IMAGEM -->
        <div class="row mb-4 mt-4">
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
            <!--<div class="col-xxl-6 mt-3">
                <label class="mb-2 fw-bold">Imagem Individual 2</label>
                <div class="corpo-upload" style="width:100%; margin-bottom:10px;"><a href="#" id="imagem_upload" name="imagem_upload" class="imagem_upload_individual_btn2 button button-secondary"><span class="dashicons dashicons-cloud-upload"></span> Carregar Imagem</a></div>
                <div style="width:100%;">
                    <input type="text" id="imagem_upload_individual2" name="imagem_upload_individual2" class="imagem_upload_individual2" value="<?php  echo $imagem_upload_individual2; ?>" />
                </div>
                <div class="preview-aluno">
                    <div class="preview-aluno-individual2">

                    </div>
                </div>
            </div>-->
            <!--<div class="col-xxl-6 mt-3">
            <label class="mb-2 fw-bold">Imagem Turma</label>
                <div class="corpo-upload" style="width:100%; margin-bottom:10px;"><a href="#" id="imagem_upload" name="imagem_upload" class="imagem_upload_turma_btn button button-secondary"><span class="dashicons dashicons-cloud-upload"></span> Carregar Imagem</a></div>
                <div style="width:100%;">
                    <input type="text" id="imagem_upload_turma" name="imagem_upload_turma" class="imagem_upload_turma" value="<?php echo $imagem_upload_turma; ?>" />
                </div>
                <div class="preview-aluno">
                    <div class="preview-aluno-turma">

                    </div>
                </div>
            </div>-->
        </div>
        <!-- IMAGEM -->
        <?php
    }

    // Salvar os valores do meta box
    function salvar_meta_box_participantes($post_id) {

        // Verificar e salvar os dados das fotos, nomes, e códigos
        if (isset($_POST['fotos_participantes']) && isset($_POST['nomes_participantes']) && isset($_POST['codigos_participantes'])) {
            $fotos = array();

            foreach ($_POST['fotos_participantes'] as $index => $caminho) {
                $fotos[] = array(
                    'caminho' => esc_url_raw($caminho),
                    'nome'    => sanitize_text_field($_POST['nomes_participantes'][$index]),
                    'codigo'  => sanitize_text_field($_POST['codigos_participantes'][$index])
                );
            }

            update_post_meta($post_id, '_fotos_participantes', $fotos); // Salvar array de fotos
        } else {
            delete_post_meta($post_id, '_fotos_participantes'); // Remover se não houver fotos
        }

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
        if (isset($_POST['evento'])) {
            update_post_meta($post_id, 'evento', $_POST['evento'] );
        }
        if (isset($_POST['link_album'])) {
            update_post_meta($post_id, 'link_album', $_POST['link_album'] );
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

    function participantes_posttype () {
        $labels = array(
            'name'                => 'Participantes',
            'singular_name'       => 'Participantes',
            'menu_name'           => 'Participantes',
            'all_items'           => 'Todos os Participantes',
            'view_item'           => 'Ver Participantes',
            'add_new_item'        => 'Adicionar novo Participante',
            'add_new'             => 'Adicionar novo',
            'edit_item'           => 'Editar Participantes',
            'update_item'         => 'Atualizar Participantes',
            'search_items'        => 'Procurar Participantes',
            'not_found'           => 'Não encontrado',
            'not_found_in_trash'  => 'Não encontrado no lixo',
        );
                
        $args = array(
            'label'               => 'Participantes',
            'description'         => 'Inclua novos Participantes',
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
            'rest_base' => 'participantes',
            'capabilities' => array(
                'edit_post'              => 'edit_participantes',
                'read_post'              => 'read_participantes',
                'delete_post'            => 'delete_participantes',
                'create_posts'           => 'create_participantes',
                'edit_posts'             => 'edit_participantes',
                'edit_others_posts'      => 'manage_participantes',
                'publish_posts'          => 'manage_participantes',
                'read_private_posts'     => 'read',
                'read'                   => 'read',
                'delete_posts'           => 'manage_participantes',
                'delete_private_posts'   => 'manage_participantes',
                'delete_published_posts' => 'manage_participantes',
                'delete_others_posts'    => 'manage_participantes',
                'edit_private_posts'     => 'edit_participantes',
                'edit_published_posts'   => 'edit_participantes'
            )
        );        
        register_post_type( 'participantes', $args );
    }

    public static function adicionar_capacidades_para_administrador() {
        $administrator_role = get_role( 'administrator' );
        // POST
        $administrator_role->add_cap('edit_participantes');
        $administrator_role->add_cap('read_participantes');
        $administrator_role->add_cap('delete_participantes');
        $administrator_role->add_cap('create_participantes');
        $administrator_role->add_cap('manage_participantes');
        $administrator_role->add_cap('edit_participantes');
        // POST
    }
}

Participantes::getInstance();
