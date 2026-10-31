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
        add_action( 'init', [$this, 'registrar_metas_alunos'], 20 );
        add_action( 'admin_init', [$this,'adicionar_capacidades_para_administrador']);
        add_action( 'add_meta_boxes', [$this,'adicionar_meta_box_alunos']);
        add_action( 'save_post', [$this,'salvar_meta_box_alunos']);
        add_action( 'pmxi_saved_post', [$this, 'importar_alunos_wp_all_import'], 10, 3 );
    }

    function registrar_metas_alunos() {
        $auth_callback = function($allowed, $meta_key, $post_id) {
            return current_user_can('edit_post', $post_id);
        };

        register_post_meta('alunos', 'serie', array(
            'type'              => 'integer',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => $auth_callback,
        ));

        register_post_meta('alunos', 'serie_nome', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => $auth_callback,
        ));

        register_post_meta('alunos', 'turma', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => $auth_callback,
        ));
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
        add_meta_box(
            'meta-box-alunos-shortcodes',
            'Shortcodes do Aluno',
            [$this,'exibir_meta_box_shortcodes_aluno'],
            'alunos',
            'normal',
            'low'
        );
    }

    // Documentação dos shortcodes exibida dentro do post type ALUNOS
    function exibir_meta_box_shortcodes_aluno() {
        ?>
        <div style="padding: 10px;">
            <p style="margin-bottom:15px; font-size:13px; color:#444;">
                Use os shortcodes abaixo em qualquer página para exibir dados do aluno logado.
                A sessão do aluno expira em <strong>10 minutos</strong>.
            </p>

            <div style="margin-bottom: 20px; padding: 12px; background: #f9f9f9; border-left: 4px solid #d32f2f;">
                <strong style="display:block; margin-bottom:5px;">Nome do Aluno</strong>
                <code>[nome_aluno]</code>
                <p style="font-size:13px; color:#666; margin-top:5px;">
                    Exibe somente o nome do aluno logado. Sem sessão ativa, o valor fica vazio.
                </p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size:13px; color:#666; background: #fff; padding: 10px; border: 1px solid #eee;">
                    <div>
                        <strong>Conteúdo:</strong><br>
                        - <code>tag="h2"</code> (span, p, div, strong, h1...h6)<br>
                        - <code>label="Aluno:"</code><br>
                        - <code>mostrar_label="false"</code><br>
                        - <code>ocultar_sem_sessao="true"</code>
                    </div>
                    <div>
                        <strong>Estilo do nome:</strong><br>
                        - <code>cor="#333333"</code><br>
                        - <code>tamanho="24px"</code><br>
                        - <code>peso="bold"</code><br>
                        - <code>fonte="Arial"</code>
                    </div>
                    <div>
                        <strong>Estilo do rótulo:</strong><br>
                        - <code>cor_label="#666666"</code><br>
                        - <code>peso_label="bold"</code><br>
                        - <code>tamanho_label="14px"</code>
                    </div>
                    <div>
                        <strong>Container:</strong><br>
                        - <code>alinhar="center"</code><br>
                        - <code>fundo="#f5f5f5"</code><br>
                        - <code>padding="12px"</code><br>
                        - <code>borda_raio="8px"</code>
                    </div>
                </div>

                <p style="font-size:12px; color:#888; margin-top:10px;">
                    <strong>Exemplo simples:</strong><br>
                    <code>[nome_aluno]</code>
                </p>
                <p style="font-size:12px; color:#888; margin-top:4px;">
                    <strong>Exemplo formatado:</strong><br>
                    <code>[nome_aluno tag="h2" tamanho="24px" peso="bold" cor="#333333" alinhar="center" fonte="Arial" fundo="#f5f5f5" padding="12px" borda_raio="8px"]</code>
                </p>
                <p style="font-size:12px; color:#888; margin-top:4px;">
                    <strong>Exemplo com rótulo:</strong><br>
                    <code>[nome_aluno label="Aluno:" tamanho="20px" peso="600" cor="#d32f2f" cor_label="#666666" peso_label="bold" tamanho_label="14px"]</code>
                </p>
            </div>

            <div style="padding: 12px; background: #f9f9f9; border-left: 4px solid #999;">
                <strong style="display:block; margin-bottom:5px;">Dados completos do aluno</strong>
                <code>[dados_aluno]</code>
                <p style="font-size:13px; color:#666; margin-top:5px;">
                    Exibe nome, escola, série/turma, unidade e data de nascimento. Aceita atributos de visibilidade
                    (<code>mostrar_nome</code>, <code>mostrar_escola</code>, <code>mostrar_turma</code>, <code>mostrar_unidade</code>, <code>mostrar_data</code>),
                    rótulos (<code>label_nome</code>, <code>label_escola</code>, <code>label_turma</code>, <code>label_unidade</code>, <code>label_data</code>) e estilo
                    (<code>cor</code>, <code>tamanho</code>, <code>peso</code>, <code>fonte</code>, <code>fundo</code>, <code>padding</code>, <code>borda_raio</code>, <code>alinhar</code>, <code>espacamento</code>).
                </p>
            </div>
        </div>
        <?php
    }

    // Callback para exibir o conteúdo do meta box
    function exibir_meta_box_alunos($post) {

        $nome = get_post_meta($post->ID, 'nome', true);
        $ultimo_nome = get_post_meta($post->ID, 'ultimo_nome', true);
        $senha_nome = get_post_meta($post->ID, 'senha_nome', true);
        $data_nascimento = get_post_meta($post->ID, 'data_nascimento', true);
        if ($data_nascimento && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_nascimento)) {
            $data_nascimento = date('d/m/Y', strtotime($data_nascimento));
        }
        $escola = get_post_meta($post->ID, 'escola', true);
        $escola = !empty($escola) ? explode(',', $escola) : [];

        $unidade = get_post_meta($post->ID, 'unidade', true);
        $unidade = !empty($unidade) ? explode(',', $unidade) : [];

        $serie = absint(get_post_meta($post->ID, 'serie', true));
        $turma_meta = get_post_meta($post->ID, 'turma', true);
        $turma = is_scalar($turma_meta) ? trim((string) $turma_meta) : '';

        // Compatibilidade com alunos ainda não processados pela migração.
        if (!$serie && $turma !== '' && ctype_digit($turma)) {
            $turma_legada = get_post((int) $turma);
            if ($turma_legada && $turma_legada->post_type === 'turmas') {
                list($serie_nome_legada, $turma_legada_nome) = \Apreas\Series::quebrar_serie_turma($turma_legada->post_title);
                $serie_encontrada = \Apreas\Series::encontrar_serie_por_titulo($serie_nome_legada);
                if ($serie_encontrada) {
                    $serie = $serie_encontrada;
                    $turma = $turma_legada_nome;
                }
            }
        }
        // var_dump('SENHA_NOME: '.$senha_nome);
        // var_dump('DATA_NASCIMENTO: '.$data_nascimento);
        // var_dump($escola);

        $imagem_upload_individual = get_post_meta($post->ID, 'imagem_upload_individual', true);
        $imagem_upload_individual2 = get_post_meta($post->ID, 'imagem_upload_individual2', true);
        $imagem_upload_turma = get_post_meta($post->ID, 'imagem_upload_turma', true);
        $faltou = get_post_meta($post->ID, 'faltou', true);

        // ESCOLAS
        $args = array(
            'post_type' => 'escolas',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        $escolas_query = new \WP_Query($args);
        // ESCOLAS

        // UNIDADES
        $args_unidades = array(
            'post_type' => 'unidades',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        $unidades_query = new \WP_Query($args_unidades);
        // UNIDADES

        // SÉRIES
        $args_series = array(
            'post_type' => 'series',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        $series_query = new \WP_Query($args_series);
        // SÉRIES
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

                // Mask for data_nascimento: dd/mm/aaaa
                $('#data_nascimento').on('input', function() {
                    var v = $(this).val();
                    v = v.replace(/\D/g, "");
                    if (v.length > 2) v = v.substring(0,2) + "/" + v.substring(2);
                    if (v.length > 5) v = v.substring(0,5) + "/" + v.substring(5, 9);
                    $(this).val(v);
                });
            });
        </script>
        <?php wp_nonce_field('apreas_save_alunos', 'apreas_alunos_nonce'); ?>
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
                    <input type="text" id="data_nascimento" name="data_nascimento" class="form-control" placeholder="dd/mm/aaaa" maxlength="10" value="<?php echo esc_attr($data_nascimento); ?>" />
                </div>
            </div>
        </div>
        <!-- ESCOLAS -->
        <div class="row mt-4">
            <div class="col">
                <div class="form-group">
                    <label for="escola" class="mb-2 fw-bold">Escola</label>
                    <select id="escola" name="escola" class="form-control">
                        <option value=""></option>
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
                    <select id="unidade" name="unidade" class="form-control">
                        <option value=""></option>
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
        <!-- SÉRIE E TURMA -->
        <div class="row mt-3">
            <div class="col">
                <div class="form-group">
                    <label for="serie" class="mb-2 fw-bold">Série</label>
                    <select id="serie" name="serie" class="form-control">
                        <option value="">Selecione a série</option>
                        <?php while ($series_query->have_posts()) : $series_query->the_post(); ?>
                            <option value="<?php echo esc_attr(get_the_ID()); ?>" <?php selected($serie, get_the_ID()); ?>><?php echo esc_html(get_the_title()); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="turma" class="mb-2 fw-bold">Turma</label>
                    <input type="text" id="turma" name="turma" class="form-control" value="<?php echo esc_attr($turma); ?>" />
                </div>
            </div>
        </div>
        <!-- SÉRIE E TURMA -->
        <!-- Faltou -->
        <div class="row mt-4 mb-2">
            <div class="col">
                <div class="form-group">
                    <label class="mb-2 fw-bold" style="cursor:pointer;">
                        <input type="checkbox" name="faltou" value="1" <?php checked($faltou, '1'); ?> />
                        Aluno Faltou? (Marque se o aluno não compareceu e portanto não possui fotos)
                    </label>
                </div>
            </div>
        </div>
        <!-- Faltou -->
        <!-- IMAGEM -->
        <div class="row mb-4">
            <div class="col-xxl-6 mt-3">
                <label class="mb-2 fw-bold">Foto Individual</label>
                <div class="corpo-upload" style="width:100%; margin-bottom:10px;"><a href="#" id="imagem_upload" name="imagem_upload" class="imagem_upload_individual_btn button button-secondary"><span class="dashicons dashicons-cloud-upload"></span> Carregar Imagem</a></div>
                <div style="width:100%;">
                    <input type="text" id="imagem_upload_individual" name="imagem_upload_individual" class="imagem_upload_individual" value="<?php  echo $imagem_upload_individual; ?>" />
                </div>
                <p style="font-size:12px; color:#666; margin-top:6px;"><strong>Para exibir no site:</strong> Adicione um elemento de Imagem e defina sua <strong>Classe CSS</strong> como <code>img_individual</code></p>
                <div class="preview-aluno">
                    <div class="preview-aluno-individual">

                    </div>
                </div>
            </div>
            <div class="col-xxl-6 mt-3">
                <label class="mb-2 fw-bold">Foto Divertida</label>
                <div class="corpo-upload" style="width:100%; margin-bottom:10px;"><a href="#" id="imagem_upload" name="imagem_upload" class="imagem_upload_individual_btn2 button button-secondary"><span class="dashicons dashicons-cloud-upload"></span> Carregar Imagem</a></div>
                <div style="width:100%;">
                    <input type="text" id="imagem_upload_individual2" name="imagem_upload_individual2" class="imagem_upload_individual2" value="<?php  echo $imagem_upload_individual2; ?>" />
                </div>
                <p style="font-size:12px; color:#666; margin-top:6px;"><strong>Para exibir no site:</strong> Adicione um elemento de Imagem e defina sua <strong>Classe CSS</strong> como <code>img_divertida</code></p>
                <div class="preview-aluno">
                    <div class="preview-aluno-individual2">

                    </div>
                </div>
            </div>
            <div class="col-xxl-6 mt-3">
            <label class="mb-2 fw-bold">Foto Coletiva</label>
                <div class="corpo-upload" style="width:100%; margin-bottom:10px;"><a href="#" id="imagem_upload" name="imagem_upload" class="imagem_upload_turma_btn button button-secondary"><span class="dashicons dashicons-cloud-upload"></span> Carregar Imagem</a></div>
                <div style="width:100%;">
                    <input type="text" id="imagem_upload_turma" name="imagem_upload_turma" class="imagem_upload_turma" value="<?php echo $imagem_upload_turma; ?>" />
                </div>
                <p style="font-size:12px; color:#666; margin-top:6px;"><strong>Para exibir no site:</strong> Adicione um elemento de Imagem e defina sua <strong>Classe CSS</strong> como <code>img_coletivo</code></p>
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
        if (get_post_type($post_id) !== 'alunos') return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['apreas_alunos_nonce']) || !wp_verify_nonce($_POST['apreas_alunos_nonce'], 'apreas_save_alunos')) return;
        if (!current_user_can('edit_post', $post_id)) return;

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
            $data_nascimento = sanitize_text_field($_POST['data_nascimento']);
            
            // Tratamento robusto para salvar manualmente
            if (strpos($data_nascimento, '/') !== false) {
                $parts = explode('/', $data_nascimento);
                if (count($parts) === 3) {
                    $dia = (int)$parts[0];
                    $mes = (int)$parts[1];
                    $ano = (int)$parts[2];

                    // Se o mês for > 12, inverteu (MM/DD/YYYY)
                    if ($mes > 12 && $dia <= 12) {
                        $data_nascimento = sprintf('%04d-%02d-%02d', $ano, $dia, $mes);
                    } else {
                        $data_nascimento = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                    }
                }
            }
            update_post_meta($post_id, 'data_nascimento', $data_nascimento);
        }
        if (isset($_POST['escola'])) {
            update_post_meta($post_id, 'escola', $_POST['escola'] );
        }
        if (isset($_POST['unidade'])) {
            update_post_meta($post_id, 'unidade', $_POST['unidade'] );
        }
        if (array_key_exists('serie', $_POST)) {
            $serie_id = absint($_POST['serie']);
            if ($serie_id && get_post_type($serie_id) !== 'series') {
                $serie_id = 0;
            }
            update_post_meta($post_id, 'serie', $serie_id);
            update_post_meta($post_id, 'serie_nome', $serie_id ? get_the_title($serie_id) : '');
        }
        if (array_key_exists('turma', $_POST)) {
            update_post_meta($post_id, 'turma', sanitize_text_field($_POST['turma']));
        }
        $campos_imagem = [
            'imagem_upload_individual',
            'imagem_upload_individual2',
            'imagem_upload_turma',
        ];
        foreach ($campos_imagem as $campo_imagem) {
            if (isset($_POST[$campo_imagem])) {
                if (function_exists('apreas_imagem_limpar_cache')) {
                    // Limpa o "Sim/Não" em cache da URL antiga e da nova.
                    apreas_imagem_limpar_cache(get_post_meta($post_id, $campo_imagem, true));
                    apreas_imagem_limpar_cache($_POST[$campo_imagem]);
                }
                update_post_meta($post_id, $campo_imagem, $_POST[$campo_imagem]);
            }
        }
        
        $faltou = isset($_POST['faltou']) ? '1' : '0';
        update_post_meta($post_id, 'faltou', $faltou);
    }

    /**
     * Integração manual com WP All Import (Versão Gratuita)
     * Salva os metadados dos Alunos que a versão free não importa nativamente.
     */
    public function importar_alunos_wp_all_import($post_id, $xml_node, $is_update) {
        if (get_post_type($post_id) !== 'alunos') return;

        // Converte o objeto XML para array para facilitar o acesso
        $data = json_decode(json_encode($xml_node), true);

        $tem_campo_serie = array_key_exists('serie', $data) || array_key_exists('série', $data);
        $tem_campo_turma = array_key_exists('turma', $data);
        $serie_importada = array_key_exists('serie', $data) ? $data['serie'] : (isset($data['série']) ? $data['série'] : '');
        $turma_importada = $tem_campo_turma ? $data['turma'] : '';
        if (!is_scalar($serie_importada)) $serie_importada = '';
        if (!is_scalar($turma_importada)) $turma_importada = '';

        // Mapeamento de Tags do XML para Meta Keys do Banco
        $meta_to_import = [
            'data_nascimento' => isset($data['data_nascimento']) ? $data['data_nascimento'] : (isset($data['data nascimento']) ? $data['data nascimento'] : ''),
            'escola'          => isset($data['escola']) ? $data['escola'] : '',
            'unidade'         => isset($data['unidade']) ? $data['unidade'] : '',
            'nome'            => isset($data['nome']) ? $data['nome'] : '',
            'ultimo_nome'     => isset($data['ultimo_nome']) ? $data['ultimo_nome'] : (isset($data['ultimo nome']) ? $data['ultimo nome'] : ''),
            'faltou'          => isset($data['faltou']) ? $data['faltou'] : '',
            'imagem_upload_individual'  => isset($data['fotoindividual']) ? $data['fotoindividual'] : (isset($data['foto_individual']) ? $data['foto_individual'] : (isset($data['imagem_upload_individual']) ? $data['imagem_upload_individual'] : (isset($data['foto individual']) ? $data['foto individual'] : ''))),
            'imagem_upload_individual2' => isset($data['fotodivertida']) ? $data['fotodivertida'] : (isset($data['foto_divertida']) ? $data['foto_divertida'] : (isset($data['imagem_upload_individual2']) ? $data['imagem_upload_individual2'] : (isset($data['foto divertida']) ? $data['foto divertida'] : ''))),
            'imagem_upload_turma'       => isset($data['fotocoletiva']) ? $data['fotocoletiva'] : (isset($data['foto_coletiva']) ? $data['foto_coletiva'] : (isset($data['imagem_upload_turma']) ? $data['imagem_upload_turma'] : (isset($data['foto coletiva']) ? $data['foto coletiva'] : ''))),
        ];

        foreach ($meta_to_import as $key => $value) {
            if (!empty($value) || $value === '0' || $key === 'faltou') {
                // Tratamento robusto para Data de Nascimento
                if ($key === 'data_nascimento') {
                    $value = trim($value);
                    
                    if (strpos($value, '/') !== false) {
                        $parts = explode('/', $value);
                        if (count($parts) === 3) {
                            $dia = (int)$parts[0];
                            $mes = (int)$parts[1];
                            $ano = (int)$parts[2];

                            if ($mes > 12 && $dia <= 12) {
                                $value = sprintf('%04d-%02d-%02d', $ano, $dia, $mes);
                            } else {
                                $value = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                            }
                        }
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        // Formato correto, mantém
                    } else {
                        $time = strtotime($value);
                        if ($time) $value = date('Y-m-d', $time);
                    }
                }
                
                if ($key === 'faltou') {
                    $v = strtolower(trim($value));
                    $value = ($v === 'sim' || $v === 'true' || $v === '1' || $v === 'yes' || $v === 'verdadeiro' || $v === 'v') ? '1' : '0';
                }
                update_post_meta($post_id, $key, $value);
            }
        }

        // Série e turma são campos diferentes no cadastro de Alunos.
        $serie_id = 0;
        $serie_nome = '';
        $turma_nome = trim((string) $turma_importada);
        $turma_foi_separada = false;

        if ($tem_campo_serie && trim((string) $serie_importada) !== '') {
            $serie_id = absint($serie_importada);
            if (!$serie_id || get_post_type($serie_id) !== 'series') {
                // Valores numéricos também podem representar o nome da série.
                $serie_id = \Apreas\Series::obter_serie_por_titulo(trim((string) $serie_importada));
            }
            if ($serie_id) {
                $serie_nome = get_the_title($serie_id);
            }
        } elseif (!$tem_campo_serie && $tem_campo_turma && $turma_nome !== '') {
            // Compatibilidade com importações antigas: ID do post ou "3º Ano A".
            if (ctype_digit($turma_nome)) {
                $turma_legada = get_post((int) $turma_nome);
                if ($turma_legada && $turma_legada->post_type === 'turmas') {
                    list($serie_nome, $turma_nome) = \Apreas\Series::quebrar_serie_turma($turma_legada->post_title);
                    $turma_foi_separada = true;
                }
            } else {
                list($serie_encontrada, $turma_encontrada) = \Apreas\Series::quebrar_serie_turma($turma_nome);
                if ($turma_encontrada !== '') {
                    $serie_nome = $serie_encontrada;
                    $turma_nome = $turma_encontrada;
                    $turma_foi_separada = true;
                }
            }

            if ($serie_nome !== '') {
                $serie_id = \Apreas\Series::obter_serie_por_titulo($serie_nome);
            }
        }

        if ($serie_id) {
            update_post_meta($post_id, 'serie', $serie_id);
            update_post_meta($post_id, 'serie_nome', $serie_nome);
        }

        if ($tem_campo_turma && ($serie_id || $turma_foi_separada || !$tem_campo_serie)) {
            update_post_meta($post_id, 'turma', $turma_nome);
        }

        // Gera a Senha Nome automaticamente caso não tenha sido importada
        $nome = get_post_meta($post_id, 'nome', true);
        $u_nome = get_post_meta($post_id, 'ultimo_nome', true);
        if ($nome && $u_nome) {
            $senha = sanitize_title($nome) . '_' . sanitize_title($u_nome);
            update_post_meta($post_id, 'senha_nome', str_replace('-', '', $senha));
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
