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
        add_shortcode('data_limite_fotos', [$this, 'shortcode_data_limite_fotos']);
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
        add_meta_box(
            'meta-box-lote-two',
            'LOTE 2', 
            [$this,'exibir_lote_two'], 
            'escolas', 
            'normal', 
            'high' 
        );
        add_meta_box(
            'meta-box-data-limite-fotos',
            'Data Limite das Fotos',
            [$this,'exibir_meta_box_data_limite_fotos'],
            'escolas',
            'normal',
            'high'
        );
        add_meta_box(
            'meta-box-shortcodes-gerais',
            'Shortcodes Gerais',
            [$this,'exibir_meta_box_shortcodes_gerais'],
            'escolas',
            'normal',
            'low'
        );
    }

    function exibir_meta_box_shortcodes_gerais() {
        ?>
        <div style="padding: 10px;">
            <p style="margin-bottom:15px; font-size:13px; color:#444;">
                Estes shortcodes podem ser utilizados em qualquer página para exibir informações dinâmicas do aluno logado.
            </p>

            <div style="margin-bottom: 20px; padding: 12px; background: #f9f9f9; border-left: 4px solid #d32f2f;">
                <strong style="display:block; margin-bottom:5px;">Formulário de Login</strong>
                <code>[login_form]</code>
                <p style="font-size:13px; color:#666; margin-top:5px;">Exibe o formulário de acesso para alunos.</p>
            </div>

            <div style="margin-bottom: 20px; padding: 12px; background: #f9f9f9; border-left: 4px solid #d32f2f;">
                <strong style="display:block; margin-bottom:5px;">Galeria de Fotos</strong>
                <code>[galeria]</code>
                <p style="font-size:13px; color:#666; margin-top:5px;">Exibe as fotos (individual, divertida e coletiva) do aluno.</p>
            </div>

            <div style="margin-bottom: 20px; padding: 12px; background: #f9f9f9; border-left: 4px solid #d32f2f;">
                <strong style="display:block; margin-bottom:5px;">Contador de Fotos Selecionadas</strong>
                <code>[fotos_selecionadas]</code>
                <p style="font-size:13px; color:#666; margin-top:5px;">Mostra a quantidade de fotos que o aluno já escolheu.</p>
            </div>

            <div style="margin-bottom: 10px; padding: 12px; background: #f9f9f9; border-left: 4px solid #d32f2f;">
                <strong style="display:block; margin-bottom:5px;">Dados do Aluno (Customizável)</strong>
                <code>[dados_aluno]</code>
                <p style="font-size:13px; color:#666; margin-top:5px;">Exibe dados dinâmicos do aluno. Você pode usar diversos atributos:</p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size:13px; color:#666; background: #fff; padding: 10px; border: 1px solid #eee;">
                    <div>
                        <strong>Visibilidade:</strong><br>
                        - <code>mostrar_nome="false"</code><br>
                        - <code>mostrar_escola="false"</code><br>
                        - <code>mostrar_turma="false"</code><br>
                        - <code>mostrar_unidade="false"</code><br>
                        - <code>mostrar_data="false"</code>
                    </div>
                    <div>
                        <strong>Rótulos:</strong><br>
                        - <code>label_nome="Estudante:"</code><br>
                        - <code>label_escola="Instituição:"</code><br>
                        - <code>label_turma="Sala:"</code><br>
                        - <code>label_unidade="Unidade:"</code><br>
                        - <code>label_data="Data:"</code>
                    </div>
                    <div>
                        <strong>Estilo Dados:</strong><br>
                        - <code>cor="#ff0000"</code><br>
                        - <code>tamanho="18px"</code><br>
                        - <code>peso="bold"</code><br>
                        - <code>fonte="Arial"</code>
                    </div>
                    <div>
                        <strong>Estilo Rótulos:</strong><br>
                        - <code>cor_label="#000"</code><br>
                        - <code>tamanho_label="12px"</code><br>
                        - <code>peso_label="normal"</code>
                    </div>
                    <div style="grid-column: span 2;">
                        <strong>Container:</strong> <code>fundo="#eee"</code>, <code>padding="20px"</code>, <code>borda_raio="10px"</code>, <code>alinhar="center"</code>, <code>espacamento="15px"</code>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    function exibir_meta_box_data_limite_fotos($post) {
        $data_limite = get_post_meta($post->ID, 'data_limite_fotos', true);
        ?>
        <p style=" margin-top: 1rem;margin-bottom:4px; font-size:12px; color:#666;">
            Data em que as fotos desta escola serão <strong>excluídas</strong> do site.
        </p>
        <p style="margin-bottom:4px; font-size:12px; color:#666;">
            ▸ <strong>Automático</strong> (usa a escola do aluno logado):
            <code>[data_limite_fotos]</code>
        </p>
        <div class="row mt-4 mb-4">
            <div class="col-xxl-6">
                <div class="form-group">
                    <label for="data_limite_fotos" class="mb-2 fw-bold">Data Limite</label>
                    <div class="input-group">
                        <input type="text" id="data_limite_fotos" name="data_limite_fotos" class="form-control" value="<?php echo esc_attr($data_limite); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#data_limite_fotos').flatpickr({
                    enableTime: false,
                    dateFormat: "d/m/Y",
                    time_24hr: false,
                    locale: 'pt'
                });
            });
        </script>
        <?php
    }

    /**
     * Shortcode [data_limite_fotos]
     * Renderiza um <span> vazio que o login.js preenche via localStorage,
     * seguindo o mesmo padrão dos outros campos de escola (lotes, logo, etc.).
     * O campo data_limite_fotos é incluído no payload AJAX do login.
     */
    function shortcode_data_limite_fotos($atts) {
        return '<span class="data_limite_fotos_escola"></span>';
    }

    function exibir_lote_one($post) {
        // ESCOLHA
        $l1_escolha_data_inicio = get_post_meta($post->ID, 'l1_escolha_data_inicio', true);
        $l1_escolha_data_fim = get_post_meta($post->ID, 'l1_escolha_data_fim', true);
        ?>
        <div class="row mt-4 mb-4">
            <label for="nome" class="mb-4 fw-bold" style="font-size: 1rem; color:#7A7A7A;">ESCOLHA</label>
            <p style="margin-bottom:15px; font-size:12px; color:#666;">
                ▸ <strong>Shortcode dos Lotes</strong>: <code>[lotes_escola]</code>
            </p>
            <div class="col">
                <div class="form-group">
                    <label for="l1_escolha_data_inicio" class="mb-2 fw-bold">Data de Início</label>
                    <div class="input-group">
                        <input type="text" id="l1_escolha_data_inicio" name="l1_escolha_data_inicio" class="form-control" value="<?php echo esc_attr($l1_escolha_data_inicio); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="l1_escolha_data_fim" class="mb-2 fw-bold">Data de Fim</label>
                    <div class="input-group">
                        <input type="text" id="l1_escolha_data_fim" name="l1_escolha_data_fim" class="form-control" value="<?php echo esc_attr($l1_escolha_data_fim); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#l1_escolha_data_inicio').flatpickr({
                    enableTime: false,
                    dateFormat: "d/m/Y",
                    time_24hr: false,
                    locale: 'pt'
                });
                $('#l1_escolha_data_fim').flatpickr({
                    enableTime: false,
                    dateFormat: "d/m/Y",
                    time_24hr: false,
                    locale: 'pt'
                });
            });
        </script>
        
        <?php
        // ESCOLHA 

        // ENTREGA
        $l1_entrega_data = get_post_meta($post->ID, 'l1_entrega_data', true);
        ?>
        
        <hr class="my-1"/>

        <div class="row mt-4 mb-4">
            <label for="nome" class="mb-4 fw-bold" style="font-size: 1rem; color:#7A7A7A;">ENTREGA</label>
            <div class="col-xxl-6">
                <div class="form-group">
                    <label for="l1_entrega_data" class="mb-2 fw-bold">Data de Entrega</label>
                    <div class="input-group">
                        <input type="text" id="l1_entrega_data" name="l1_entrega_data" class="form-control" value="<?php echo esc_attr($l1_entrega_data); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#l1_entrega_data').flatpickr({
                    enableTime: false,
                    dateFormat: "d/m/Y",
                    time_24hr: false,
                    locale: 'pt'
                });
            });
        </script>
        <?php
        // ENTREGA
    }

    function exibir_lote_two($post) {
        // ESCOLHA
        $l2_escolha_data_inicio = get_post_meta($post->ID, 'l2_escolha_data_inicio', true);
        $l2_escolha_data_fim = get_post_meta($post->ID, 'l2_escolha_data_fim', true);
        ?>
        <div class="row mt-4 mb-4">
            <label for="nome" class="mb-4 fw-bold" style="font-size: 1rem; color:#7A7A7A;">ESCOLHA</label>
            <p style="margin-bottom:15px; font-size:12px; color:#666;">
                ▸ <strong>Shortcode dos Lotes</strong>: <code>[lotes_escola]</code>
            </p>
            <div class="col">
                <div class="form-group">
                    <label for="l2_escolha_data_inicio" class="mb-2 fw-bold">Data de Início</label>
                    <div class="input-group">
                        <input type="text" id="l2_escolha_data_inicio" name="l2_escolha_data_inicio" class="form-control" value="<?php echo esc_attr($l2_escolha_data_inicio); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="l2_escolha_data_fim" class="mb-2 fw-bold">Data de Fim</label>
                    <div class="input-group">
                        <input type="text" id="l2_escolha_data_fim" name="l2_escolha_data_fim" class="form-control" value="<?php echo esc_attr($l2_escolha_data_fim); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#l2_escolha_data_inicio').flatpickr({
                    enableTime: false,
                    dateFormat: "d/m/Y",
                    time_24hr: false,
                    locale: 'pt'
                });
                $('#l2_escolha_data_fim').flatpickr({
                    enableTime: false,
                    dateFormat: "d/m/Y",
                    time_24hr: false,
                    locale: 'pt'
                });
            });
        </script>
        
        <?php
        // ESCOLHA 

        // ENTREGA
        $l2_entrega_data = get_post_meta($post->ID, 'l2_entrega_data', true);
        ?>
        
        <hr class="my-1"/>

        <div class="row mt-4 mb-4">
            <label for="nome" class="mb-4 fw-bold" style="font-size: 1rem; color:#7A7A7A;">ENTREGA</label>
            <div class="col-xxl-6">
                <div class="form-group">
                    <label for="l2_entrega_data" class="mb-2 fw-bold">Data de Entrega</label>
                    <div class="input-group">
                        <input type="text" id="l2_entrega_data" name="l2_entrega_data" class="form-control" value="<?php echo esc_attr($l2_entrega_data); ?>" />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="dashicons dashicons-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#l2_entrega_data').flatpickr({
                    enableTime: false,
                    dateFormat: "d/m/Y",
                    time_24hr: false,
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
                <p style="margin-bottom:10px; font-size:12px; color:#666;">
                    ▸ <strong>Shortcode da Logo</strong>: <code>[imagem_logo_escola]</code>
                </p>
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

        // DATA LIMITE FOTOS
        if (isset($_POST['data_limite_fotos'])) {
            update_post_meta($post_id, 'data_limite_fotos', sanitize_text_field($_POST['data_limite_fotos']));
        }
        // DATA LIMITE FOTOS

        // LOTE 1 
        if (isset($_POST['l1_escolha_data_inicio'])) {
            update_post_meta($post_id, 'l1_escolha_data_inicio', sanitize_text_field($_POST['l1_escolha_data_inicio']));
        }
        if (isset($_POST['l1_escolha_data_fim'])) {
            update_post_meta($post_id, 'l1_escolha_data_fim', sanitize_text_field($_POST['l1_escolha_data_fim']));
        }
        if (isset($_POST['l1_entrega_data'])) {
            update_post_meta($post_id, 'l1_entrega_data', sanitize_text_field($_POST['l1_entrega_data']));
        }
        // LOTE 1

        // LOTE 2 
        if (isset($_POST['l2_escolha_data_inicio'])) {
            update_post_meta($post_id, 'l2_escolha_data_inicio', sanitize_text_field($_POST['l2_escolha_data_inicio']));
        }
        if (isset($_POST['l2_escolha_data_fim'])) {
            update_post_meta($post_id, 'l2_escolha_data_fim', sanitize_text_field($_POST['l2_escolha_data_fim']));
        }
        if (isset($_POST['l2_entrega_data'])) {
            update_post_meta($post_id, 'l2_entrega_data', sanitize_text_field($_POST['l2_entrega_data']));
        }
        // LOTE 2

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
