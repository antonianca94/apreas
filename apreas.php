<?php
/*
Plugin Name: Apreas WP Plugin
Plugin URI: https://apreas.com.br/
Description: Recursos extras para os Alunos.
Version: 2.2.1
Author: Apreas Development Team
Author URI: https://apreas.com.br/
Text Domain: apreas
License: GPL2
*/

if (!defined("ABSPATH")) {
    exit();
}

spl_autoload_register(function ($class) {
    $prefix = "Apreas\\";
    $base_dir = __DIR__ . "/includes/";
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file =
        $base_dir .
        "class-wp-apreas-" .
        strtolower(str_replace("\\", "-", $relative_class)) .
        ".php";
    if (file_exists($file)) {
        require $file;
    }
});

class APREAS_Plugin
{
    private static $instance;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init();
    }

    private function init()
    {
        add_action("plugins_loaded", [$this, "plugin_loaded"]);
        add_action("admin_enqueue_scripts", [$this, "load_admin_assets"]);
        add_action("wp_enqueue_scripts", [$this, "load_frontend_assets"]);
        add_action("wp_footer", [$this, "render_minicart_html"]);
    }

    public function plugin_loaded()
    {
        $Login = \Apreas\Login::getInstance();
        // Escolas precisa ser instanciada sempre (frontend + admin) para registrar o shortcode
        $Escolas = \Apreas\Escolas::getInstance();
        // Campos extras do checkout WooCommerce (substitui plugin externo)
        $Checkout = \Apreas\Checkout::getInstance();
        $Settings = \Apreas\Settings::getInstance();

        // ─────────────────────────────────────────────
        // AJAX — Mini Carrinho: dados do carrinho
        // ─────────────────────────────────────────────
        add_action('wp_ajax_apreas_get_minicart_data',        [$this, 'ajax_get_minicart_data']);
        add_action('wp_ajax_nopriv_apreas_get_minicart_data', [$this, 'ajax_get_minicart_data']);

        // AJAX — Mini Carrinho: remover item
        add_action('wp_ajax_apreas_remove_minicart_item',        [$this, 'ajax_remove_minicart_item']);
        add_action('wp_ajax_nopriv_apreas_remove_minicart_item', [$this, 'ajax_remove_minicart_item']);

        if (is_admin()) {
            // INSTANCIAS
            $Alunos = \Apreas\Alunos::getInstance();
            $Unidades = \Apreas\Unidades::getInstance();
            $Turmas = \Apreas\Turmas::getInstance();

            $Participantes = \Apreas\Participantes::getInstance();
            $Eventos = \Apreas\Eventos::getInstance();
            // INSTANCIAS

            //COLUNA ALUNOS
            add_filter("manage_alunos_posts_columns", function ($columns) {
                $new_columns = [
                    "cb" => $columns["cb"], // Checkbox de seleção
                    "title" => "Aluno", // Nome do Aluno
                    "escola" => "Escola",
                    "turma" => "Turma",
                    "unidade" => "Unidade",
                    "data_nascimento" => "Data de Nascimento",
                    "date" => "Data",
                    "faltou" => "Faltou?",
                ];

                return $new_columns;
            });

            add_action(
                "manage_alunos_posts_custom_column",
                function ($column, $post_id) {
                    switch ($column) {
                        case "faltou":
                            $faltou = get_post_meta($post_id, "faltou", true);
                            $is_checked = ($faltou == "1");
                            $toggle_class = $is_checked ? 'apreas-toggle apreas-toggle--on' : 'apreas-toggle';
                            $label_text   = $is_checked ? 'Faltou' : 'Presente';
                            $label_color  = $is_checked ? '#c0392b' : '#27ae60';
                            echo '<div class="' . $toggle_class . '" data-post-id="' . $post_id . '" title="Clique para alternar">';
                            echo '  <div class="apreas-toggle__track"><div class="apreas-toggle__thumb"></div></div>';
                            echo '  <span class="apreas-toggle__label" style="color:' . $label_color . '">' . $label_text . '</span>';
                            echo '</div>';
                            break;

                        case "escola":
                            // Buscamos o ID da escola que está associado a este aluno
                            $escola_id = get_post_meta(
                                $post_id,
                                "escola",
                                true
                            );
                            if ($escola_id) {
                                echo get_the_title($escola_id);
                            } else {
                                echo "—";
                            }
                            break;

                        case "turma":
                            $turma_id = get_post_meta($post_id, "turma", true);
                            if ($turma_id) {
                                echo get_the_title($turma_id);
                            } else {
                                echo "—";
                            }
                            break;

                        case "unidade":
                            $unidade_id = get_post_meta(
                                $post_id,
                                "unidade",
                                true
                            );
                            if ($unidade_id) {
                                echo get_the_title($unidade_id);
                            } else {
                                echo "—";
                            }
                            break;
                        case "data_nascimento":
                            $data = get_post_meta(
                                $post_id,
                                "data_nascimento",
                                true
                            ); // Verifique se é 'data_nascimento' ou 'data_ascimento'
                            if ($data) {
                                // Se a data vier do banco como 2026-04-20, isso transforma em 20/04/2026
                                echo date("d/m/Y", strtotime($data));
                            } else {
                                echo "—";
                            }
                            break;
                    }
                },
                10,
                2
            );

            add_filter("manage_edit-alunos_sortable_columns", function (
                $sortable_columns
            ) {
                $sortable_columns["escola"] = "escola";
                $sortable_columns["turma"] = "turma";
                $sortable_columns["unidade"] = "unidade";
                $sortable_columns["data_nascimento"] = "data_nascimento";
                return $sortable_columns;
            });

            add_action("pre_get_posts", function ($query) {
                if (!is_admin() || !$query->is_main_query()) {
                    return;
                }

                $orderby = $query->get("orderby");

                switch ($orderby) {
                    case "escola":
                    case "turma":
                    case "unidade":
                        $query->set("meta_key", $orderby); // Usa o slug da coluna como chave do meta_data
                        $query->set("orderby", "meta_value_num"); // Ordena como número (já que guarda o ID)

                        break;

                    case "data_nascimento":
                        $query->set("meta_key", "data_nascimento");
                        $query->set("orderby", "meta_value");
                        // Se a data estiver no formato YYYY-MM-DD, a ordenação de texto funciona perfeitamente.

                        break;
                }
            });

            add_action("restrict_manage_posts", function ($post_type) {
                if ($post_type !== "alunos") {
                    return;
                }

                // --- FILTRO DE ESCOLA ---
                $escolas = get_posts([
                    "post_type" => "escolas", // Verifique se o slug do CPT de escolas é 'escola'
                    "posts_per_page" => -1,
                    "orderby" => "title",
                    "order" => "ASC",
                ]);

                $escola_sel = isset($_GET["filtro_escola"])
                    ? $_GET["filtro_escola"]
                    : "";

                echo '<select name="filtro_escola">';
                echo '<option value="">Todas as Escolas</option>';
                foreach ($escolas as $esc) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        $esc->ID,
                        selected($escola_sel, $esc->ID, false),
                        $esc->post_title
                    );
                }
                echo "</select>";

                // --- FILTRO DE TURMA ---
                $turmas = get_posts([
                    "post_type" => "turmas", // Verifique se o slug do CPT de turmas é 'turma'
                    "posts_per_page" => -1,
                    "orderby" => "title",
                    "order" => "ASC",
                ]);

                $turma_sel = isset($_GET["filtro_turma"])
                    ? $_GET["filtro_turma"]
                    : "";

                echo '<select name="filtro_turma">';
                echo '<option value="">Todas as Turmas</option>';
                foreach ($turmas as $tur) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        $tur->ID,
                        selected($turma_sel, $tur->ID, false),
                        $tur->post_title
                    );
                }
                echo "</select>";
            });

            add_action("pre_get_posts", function ($query) {
                global $pagenow;

                if (
                    !is_admin() ||
                    $pagenow !== "edit.php" ||
                    $query->get("post_type") !== "alunos" ||
                    !$query->is_main_query()
                ) {
                    return;
                }

                $meta_query = [];

                // Se selecionou Escola
                if (!empty($_GET["filtro_escola"])) {
                    $meta_query[] = [
                        "key" => "escola", // Nome da meta_key que você usa para salvar o ID da escola
                        "value" => $_GET["filtro_escola"],
                        "compare" => "=",
                    ];
                }

                // Se selecionou Turma
                if (!empty($_GET["filtro_turma"])) {
                    $meta_query[] = [
                        "key" => "turma", // Nome da meta_key que você usa para salvar o ID da turma
                        "value" => $_GET["filtro_turma"],
                        "compare" => "=",
                    ];
                }

                // Se houver algum filtro ativo, aplica na consulta
                if (count($meta_query) > 0) {
                    if (count($meta_query) > 1) {
                        $meta_query["relation"] = "AND";
                    }
                    $query->set("meta_query", $meta_query);
                }
            });

            // AJAX handler para salvar o campo 'faltou' inline da listagem
            add_action('wp_ajax_apreas_toggle_faltou', function () {
                check_ajax_referer('apreas_toggle_faltou_nonce', 'nonce');
                $post_id = intval($_POST['post_id']);
                $value   = sanitize_text_field($_POST['value']);
                if (!current_user_can('edit_post', $post_id)) {
                    wp_send_json_error('Sem permissão');
                }
                update_post_meta($post_id, 'faltou', $value === '1' ? '1' : '0');
                wp_send_json_success();
            });

            // Script inline para o toggle AJAX
            add_action('admin_footer', function () {
                global $pagenow, $typenow;
                if ($pagenow !== 'edit.php' || $typenow !== 'alunos') return;
                $nonce = wp_create_nonce('apreas_toggle_faltou_nonce');
                echo "<style>
                .apreas-toggle {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    cursor: pointer;
                    user-select: none;
                }
                .apreas-toggle__track {
                    position: relative;
                    width: 40px;
                    height: 22px;
                    background: #ccc;
                    border-radius: 999px;
                    transition: background 0.25s ease;
                    flex-shrink: 0;
                }
                .apreas-toggle--on .apreas-toggle__track {
                    background: #e74c3c;
                }
                .apreas-toggle__thumb {
                    position: absolute;
                    top: 3px;
                    left: 3px;
                    width: 16px;
                    height: 16px;
                    background: #fff;
                    border-radius: 50%;
                    box-shadow: 0 1px 4px rgba(0,0,0,.25);
                    transition: transform 0.25s ease;
                }
                .apreas-toggle--on .apreas-toggle__thumb {
                    transform: translateX(18px);
                }
                .apreas-toggle__label {
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: .3px;
                    transition: color 0.25s ease;
                    min-width: 52px;
                }
                .apreas-toggle--loading .apreas-toggle__track {
                    opacity: 0.5;
                    pointer-events: none;
                }
                </style>";
                echo "<script>
                jQuery(document).ready(function($) {
                    $(document).on('click', '.apreas-toggle', function() {
                        var el      = $(this);
                        var post_id = el.data('post-id');
                        var is_on   = el.hasClass('apreas-toggle--on');
                        var value   = is_on ? '0' : '1';
                        el.addClass('apreas-toggle--loading');
                        $.post(ajaxurl, {
                            action:  'apreas_toggle_faltou',
                            nonce:   '{$nonce}',
                            post_id: post_id,
                            value:   value
                        }, function(res) {
                            el.removeClass('apreas-toggle--loading');
                            if (res.success) {
                                el.toggleClass('apreas-toggle--on', value === '1');
                                if (value === '1') {
                                    el.find('.apreas-toggle__label').text('Faltou').css('color','#c0392b');
                                } else {
                                    el.find('.apreas-toggle__label').text('Presente').css('color','#27ae60');
                                }
                            }
                        });
                    });
                });
                </script>";
            });

            //FIM COLUNA ALUNOS

            //INICIO DA COLUNA ESCOLA

            // 1. Define as colunas e a ordem
            add_filter(
                "manage_escolas_posts_columns",
                "reorder_escolas_columns"
            );
            function reorder_escolas_columns($columns)
            {
                // Criamos um novo array com a ordem desejada
                $new_columns = [];

                // 1. Colocamos o Checkbox de seleção em primeiro (padrão do WP)
                if (isset($columns["cb"])) {
                    $new_columns["cb"] = $columns["cb"];
                }

                // 2. Inserimos o ID como a primeira coluna de dados
                $new_columns["post_id"] = "ID";

                // 3. Adicionamos o Título
                if (isset($columns["title"])) {
                    $new_columns["title"] = $columns["title"];
                }

                // 4. Adicionamos a Data
                if (isset($columns["date"])) {
                    $new_columns["date"] = $columns["date"];
                }

                // Caso existam outras colunas de plugins (como SEO, etc) e você queira mantê-las no final:
                /*
                foreach ($columns as $key => $value) {
                if (!isset($new_columns[$key])) {
                $new_columns[$key] = $value;
                }
                }
                */

                return $new_columns;
            }

            // 2. Preenche o valor da coluna ID
            add_action(
                "manage_escolas_posts_custom_column",
                "display_escolas_id_value",
                10,
                2
            );
            function display_escolas_id_value($column, $post_id)
            {
                if ($column === "post_id") {
                    echo "<strong>" . $post_id . "</strong>";
                }
            }

            // 3. Ajusta a largura da coluna ID via CSS para ficar discreto
            add_action("admin_head", "style_escolas_id_column");
            function style_escolas_id_column()
            {
                echo '<style type="text/css">
        .column-post_id { width: 90px !important; text-align: left; }
    </style>';
            }
            //FIM COLUNA ESCOLA

            //INICIO COLUNA TURMA
            // 1. Define as colunas e a ordem
            add_filter("manage_turmas_posts_columns", "reorder_turmas_columns");
            function reorder_turmas_columns($columns)
            {
                // Criamos um novo array com a ordem desejada
                $new_columns = [];

                // 1. Colocamos o Checkbox de seleção em primeiro (padrão do WP)
                if (isset($columns["cb"])) {
                    $new_columns["cb"] = $columns["cb"];
                }

                // 2. Inserimos o ID como a primeira coluna de dados
                $new_columns["post_id"] = "ID";

                // 3. Adicionamos o Título
                if (isset($columns["title"])) {
                    $new_columns["title"] = $columns["title"];
                }

                // 4. Adicionamos a Data
                if (isset($columns["date"])) {
                    $new_columns["date"] = $columns["date"];
                }

                // Caso existam outras colunas de plugins (como SEO, etc) e você queira mantê-las no final:
                /*
                foreach ($columns as $key => $value) {
                if (!isset($new_columns[$key])) {
                $new_columns[$key] = $value;
                }
                }
                */

                return $new_columns;
            }

            // 2. Preenche o valor da coluna ID
            add_action(
                "manage_turmas_posts_custom_column",
                "display_turmas_id_value",
                10,
                2
            );
            function display_turmas_id_value($column, $post_id)
            {
                if ($column === "post_id") {
                    echo "<strong>" . $post_id . "</strong>";
                }
            }

            // 3. Ajusta a largura da coluna ID via CSS para ficar discreto
            add_action("admin_head", "style_turmas_id_column");
            function style_turmas_id_column()
            {
                echo '<style type="text/css">
        .column-post_id { width: 90px !important; text-align: left; }
    </style>';
            }
            //FIM COLUNA TURMA

            // 1. Criar os cabeçalhos das colunas
            add_filter(
                "manage_alunos_posts_columns",
                "adicionar_colunas_imagens_alunos"
            );
            function adicionar_colunas_imagens_alunos($columns)
            {
                $columns["img_indiv_1"] = "Individual";
                $columns["img_indiv_2"] = "Divertida";
                $columns["img_turma"] = "Turma";
                return $columns;
            }

            // 2. Preencher o conteúdo das colunas
            add_action(
                "manage_alunos_posts_custom_column",
                "preencher_colunas_imagens_alunos",
                10,
                2
            );
            function preencher_colunas_imagens_alunos($column, $post_id)
            {
                switch ($column) {
                    case "img_indiv_1":
                        $imagem = get_post_meta(
                            $post_id,
                            "imagem_upload_individual",
                            true
                        );
                        exibir_status_imagem($imagem);
                        break;

                    case "img_indiv_2":
                        $imagem = get_post_meta(
                            $post_id,
                            "imagem_upload_individual2",
                            true
                        );
                        exibir_status_imagem($imagem);
                        break;

                    case "img_turma":
                        $imagem = get_post_meta(
                            $post_id,
                            "imagem_upload_turma",
                            true
                        );
                        exibir_status_imagem($imagem);
                        break;
                }
            }

            // Função auxiliar para exibir o ícone de status
            function exibir_status_imagem($valor)
            {
                if ($valor) {
                    echo '<span style="color: #46b450; font-size: 20px;" title="Preenchido">●</span> Sim';
                } else {
                    echo '<span style="color: #dc3232; font-size: 20px;" title="Vazio">○</span> Não';
                }
            }
        }
    }

    public function load_frontend_assets()
    {
        $this->enqueue_frontend_styles();
        $this->enqueue_frontend_scripts();
    }

    // ─────────────────────────────────────────────
    // MINI CARRINHO — HTML do widget (wp_footer)
    // ─────────────────────────────────────────────
    public function render_minicart_html()
    {
        // Só exibe no frontend, fora do admin e quando WooCommerce está ativo e recurso habilitado
        if (is_admin() || !function_exists('WC') || !get_option('apreas_minicart_enabled', 1)) {
            return;
        }
        $cart_url     = wc_get_cart_url();
        $checkout_url = wc_get_checkout_url();
        ?>
        <!-- Apreas Mini Carrinho Flutuante -->
        <div id="apreas-minicart-overlay" aria-hidden="true"></div>

        <button id="apreas-minicart-trigger"
                aria-label="Ver carrinho"
                aria-expanded="false"
                aria-controls="apreas-minicart-panel">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9"  cy="21" r="1"/>
                <circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <span id="apreas-minicart-badge" aria-live="polite">0</span>
        </button>

        <aside id="apreas-minicart-panel"
               role="dialog"
               aria-label="Mini Carrinho"
               aria-modal="true">

            <div class="apreas-minicart__header">
                <div class="apreas-minicart__header-title">
                    <div class="apreas-minicart__header-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9"  cy="21" r="1"/>
                            <circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                    </div>
                    Meu Carrinho
                </div>
                <button class="apreas-minicart__close" aria-label="Fechar carrinho"
                        onclick="document.getElementById('apreas-minicart-panel').classList.remove('apreas-minicart--open'); document.getElementById('apreas-minicart-overlay').classList.remove('apreas-minicart-overlay--visible');">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6"  y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div class="apreas-minicart__body">

                <!-- Empty State -->
                <div id="apreas-minicart-empty">
                    <div class="apreas-minicart-empty__icon-wrap">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9"  cy="21" r="1"/>
                            <circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                    </div>
                    <strong class="apreas-minicart-empty__title">Carrinho vazio</strong>
                    <p class="apreas-minicart-empty__text">Adicione produtos para continuar comprando.</p>
                </div>

                <!-- Items (preenchido via JS) -->
                <div id="apreas-minicart-items-wrap" style="display:none;">
                    <ul id="apreas-minicart-items"></ul>
                </div>

            </div><!-- /.apreas-minicart__body -->

            <!-- Rodapé com subtotal e botões -->
            <div class="apreas-minicart__footer">
                <div id="apreas-minicart-items-wrap-footer" style="display:none;">
                    <div class="apreas-minicart__summary">
                        <div class="apreas-minicart__summary-row">
                            <span class="apreas-minicart__summary-label">Subtotal</span>
                            <span id="apreas-minicart-subtotal-value" class="apreas-minicart__summary-value">R$&nbsp;0,00</span>
                        </div>
                        
                        <div id="apreas-minicart-fee-row" class="apreas-minicart__summary-row apreas-minicart__summary-row--fee" style="display:none;">
                            <span class="apreas-minicart__summary-label">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                                Taxa de Entrega
                            </span>
                            <span id="apreas-minicart-fee-value" class="apreas-minicart__summary-value">R$&nbsp;0,00</span>
                        </div>
                        
                        <div class="apreas-minicart__summary-total">
                            <span class="apreas-minicart__total-label">Total</span>
                            <span id="apreas-minicart-total-value" class="apreas-minicart__total-value">R$&nbsp;0,00</span>
                        </div>
                    </div>
                    
                    <div class="apreas-minicart__actions">
                        <a href="<?php echo esc_url($checkout_url); ?>" class="apreas-minicart__btn-checkout">
                            Finalizar Pedido
                        </a>
                        <a href="<?php echo esc_url($cart_url); ?>" class="apreas-minicart__btn-cart">
                            Ver Carrinho
                        </a>
                    </div>
                </div>
            </div>

        </aside><!-- /#apreas-minicart-panel -->
        <?php
    }

    // ─────────────────────────────────────────────
    // AJAX — Retorna dados do carrinho (JSON)
    // ─────────────────────────────────────────────
    public function ajax_get_minicart_data()
    {
        if (!get_option('apreas_minicart_enabled', 1)) {
            wp_send_json_error('Recurso desativado');
        }
        check_ajax_referer('apreas_minicart_nonce', 'nonce');

        if (!function_exists('WC') || !WC()->cart) {
            wp_send_json_success(['count' => 0, 'subtotal' => '', 'items' => []]);
        }

        WC()->cart->calculate_totals();

        $items = [];
        foreach (WC()->cart->get_cart() as $key => $cart_item) {
            $product = $cart_item['data'];
            $thumb   = '';
            $img_id  = $product->get_image_id();
            if ($img_id) {
                $src   = wp_get_attachment_image_src($img_id, 'thumbnail');
                $thumb = $src ? $src[0] : '';
            }
            $items[] = [
                'key'   => $key,
                'name'  => $product->get_name(),
                'qty'   => $cart_item['quantity'],
                'price' => wc_price($product->get_price()),
                'thumb' => $thumb,
            ];
        }

        wp_send_json_success([
            'count'    => WC()->cart->get_cart_contents_count(),
            'subtotal' => WC()->cart->get_cart_subtotal(),
            'fee'      => wc_price( WC()->cart->get_fee_total() ),
            'fee_raw'  => WC()->cart->get_fee_total(),
            'total'    => WC()->cart->get_total(),
            'items'    => $items,
        ]);
    }

    // ─────────────────────────────────────────────
    // AJAX — Remove item do carrinho
    // ─────────────────────────────────────────────
    public function ajax_remove_minicart_item()
    {
        if (!get_option('apreas_minicart_enabled', 1)) {
            wp_send_json_error('Recurso desativado');
        }
        check_ajax_referer('apreas_minicart_nonce', 'nonce');

        $key = sanitize_text_field($_POST['key'] ?? '');
        if (!$key || !function_exists('WC') || !WC()->cart) {
            wp_send_json_error('Chave inválida');
        }

        $removed = WC()->cart->remove_cart_item($key);
        if ($removed) {
            WC()->cart->calculate_totals();
            wp_send_json_success();
        } else {
            wp_send_json_error('Não foi possível remover o item');
        }
    }

    public function load_admin_assets()
    {
        $this->enqueue_admin_styles();
        $this->enqueue_admin_scripts();
    }

    private function enqueue_frontend_styles()
    {
        wp_enqueue_style(
            "Participantes_CSS",
            plugins_url("/admin/css/participantes.css", __FILE__),
            [],
            "1.0.33"
        );
        if (get_option('apreas_minicart_enabled', 1)) {
            wp_enqueue_style(
                "Apreas_Minicart_CSS",
                plugins_url("/admin/css/minicart.css", __FILE__),
                [],
                "1.0.5"
            );
        }
        wp_enqueue_style(
            "bootstrap",
            "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
            [],
            "5.3.0"
        );
        wp_enqueue_style(
            "sweetalert2-css",
            "https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css",
            [],
            null
        );
        wp_enqueue_style(
            "bootstrap-icons",
            "https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css",
            [],
            "1.5.0"
        );

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
        wp_add_inline_style("Participantes_CSS", $checkout_css);
    }

    private function enqueue_frontend_scripts()
    {
        wp_enqueue_script("jquery");

        wp_enqueue_script(
            "bootstrap",
            "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
            ["jquery"],
            "5.3.0",
            true
        );
        wp_enqueue_script(
            "Login_JS",
            plugins_url("/admin/js/login.js", __FILE__),
            [],
            "1.0.79",
            true
        );
        wp_enqueue_script(
            "sweetalert2-js",
            "https://cdn.jsdelivr.net/npm/sweetalert2@11",
            [],
            null,
            true
        );
        wp_enqueue_script(
            "Validation_JS",
            plugins_url("/admin/js/validation.js", __FILE__),
            [],
            "1.0.4",
            true
        );
        // Mini Carrinho Flutuante
        if (get_option('apreas_minicart_enabled', 1)) {
            wp_enqueue_script(
                "Apreas_Minicart_JS",
                plugins_url("/admin/js/minicart.js", __FILE__),
                ["jquery"],
                "1.0.0",
                true
            );
            wp_localize_script(
                "Apreas_Minicart_JS",
                "apreas_minicart",
                [
                    "ajax_url" => admin_url("admin-ajax.php"),
                    "nonce"    => wp_create_nonce("apreas_minicart_nonce"),
                    "cart_url"     => wc_get_cart_url(),
                    "checkout_url" => wc_get_checkout_url(),
                ]
            );
        }
    }

    private function enqueue_admin_styles()
    {
    }

    private function enqueue_admin_scripts()
    {
        if (!did_action("wp_enqueue_media")) {
            wp_enqueue_media();
        }
        // wp_enqueue_script('Login_JS', plugins_url('/admin/js/login.js', __FILE__), array(), '1.0.4', true);
        wp_enqueue_script(
            "Upload_JS",
            plugins_url("/admin/js/upload.js", __FILE__),
            [],
            "1.0.4",
            true
        );
        wp_enqueue_script(
            "Preview_JS",
            plugins_url("/admin/js/preview.js", __FILE__),
            [],
            "1.0.3",
            true
        );
        wp_enqueue_script(
            "select2",
            "https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js",
            ["jquery"],
            "4.1.0",
            true
        );
        wp_enqueue_style(
            "select2",
            "https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css",
            [],
            "4.1.0"
        );
        wp_enqueue_script(
            "pt-BR",
            plugins_url("/admin/js/pt-BR.js", __FILE__),
            ["select2"],
            "1.0.0",
            true
        );

        wp_enqueue_script(
            "bootstrap",
            "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
            ["jquery"],
            "5.3.0",
            true
        );
        wp_enqueue_style(
            "bootstrap",
            "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
            [],
            "5.3.0"
        );
        wp_enqueue_style(
            "Style_CSS",
            plugins_url("/admin/css/style.css", __FILE__),
            [],
            "1.0.12"
        );
    }
}

APREAS_Plugin::get_instance();
