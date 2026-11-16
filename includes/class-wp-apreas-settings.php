<?php
namespace Apreas;

if (!defined("ABSPATH")) {
    exit();
}

class Settings
{
    private static $instance;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'avisos_google']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_gcal_assets']);
        add_action('wp_ajax_apreas_gcal_testar', [$this, 'ajax_testar_conexao']);
        add_action('wp_ajax_apreas_gcal_salvar', [$this, 'ajax_salvar_gcal']);
    }

    public function add_settings_page()
    {
        add_submenu_page(
            'apreas-relatorio',
            'Configurações Apreas',
            'Configurações Apreas',
            'manage_options',
            'apreas-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings()
    {
        register_setting('apreas_settings_group', 'apreas_minicart_enabled');
        register_setting('apreas_settings_group', 'apreas_taxa_fixa_enabled');
        register_setting('apreas_settings_group', 'apreas_taxa_fixa_valor');
        register_setting('apreas_settings_group', 'apreas_custom_coupon_enabled');
        register_setting('apreas_settings_group', 'apreas_whatsapp_numero');
        register_setting('apreas_settings_group', 'apreas_primary_color');
        register_setting('apreas_settings_group', 'apreas_eventos_enabled');
        register_setting('apreas_settings_group', 'apreas_checkout_category');

        if (current_user_can('manage_options')) {
            register_setting('apreas_settings_group', 'apreas_gcal_enabled', ['sanitize_callback' => 'absint']);
            register_setting('apreas_settings_group', 'apreas_gcal_calendar_id', ['sanitize_callback' => 'sanitize_text_field']);
            register_setting('apreas_settings_group', 'apreas_gcal_service_json', ['sanitize_callback' => [$this, 'sanitizar_chave_json']]);
            register_setting('apreas_settings_group', 'apreas_eventos_tipos', ['sanitize_callback' => [$this, 'sanitizar_lista']]);
            register_setting('apreas_settings_group', 'apreas_eventos_entregas', ['sanitize_callback' => [$this, 'sanitizar_lista']]);
            register_setting('apreas_settings_group', 'apreas_eventos_pagamentos', ['sanitize_callback' => [$this, 'sanitizar_lista']]);
            register_setting('apreas_settings_group', 'apreas_eventos_controle', ['sanitize_callback' => [$this, 'sanitizar_controle']]);
        }
    }

    /**
     * Limpa uma lista cadastrável: trim, remove vazios e duplicados, reindexa.
     * Campo ausente (nenhuma linha) vira lista vazia, não preserva o valor antigo.
     */
    public function sanitizar_lista($valor)
    {
        if (!is_array($valor)) {
            $valor = [];
        }

        $limpa = [];
        foreach ($valor as $item) {
            $item = sanitize_text_field(is_scalar($item) ? (string) $item : '');
            if ($item !== '' && !in_array($item, $limpa, true)) {
                $limpa[] = $item;
            }
        }

        return $limpa;
    }

    /**
     * Controle: rótulos editáveis com chave estável (hidden) por linha.
     * Linha nova sem chave ganha slug do rótulo; renomear não troca a chave.
     */
    public function sanitizar_controle($valor)
    {
        if (!is_array($valor)) {
            $valor = [];
        }

        $chaves = (isset($_POST['apreas_eventos_controle_chave']) && is_array($_POST['apreas_eventos_controle_chave']))
            ? $_POST['apreas_eventos_controle_chave']
            : [];

        $itens = [];
        $usadas = [];
        foreach ($valor as $i => $rotulo) {
            $rotulo = sanitize_text_field(is_scalar($rotulo) ? (string) $rotulo : '');
            if ($rotulo === '') {
                continue;
            }

            $chave = isset($chaves[$i]) ? sanitize_key($chaves[$i]) : '';
            if ($chave === '') {
                $chave = sanitize_title($rotulo);
            }
            if ($chave === '') {
                $chave = 'controle';
            }

            $base = $chave;
            $n = 2;
            while (in_array($chave, $usadas, true)) {
                $chave = $base . '-' . $n;
                $n++;
            }
            $usadas[] = $chave;

            $itens[] = ['chave' => $chave, 'rotulo' => $rotulo];
        }

        return $itens;
    }

    /**
     * A chave JSON é validada inteira: o private_key contém sequências \n que
     * não podem ser alteradas, por isso o valor não passa por sanitize_text_field.
     */
    public function sanitizar_chave_json($valor)
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return '';
        }

        $json = json_decode($valor, true);
        if (is_array($json) && !empty($json['client_email']) && !empty($json['private_key'])) {
            return $valor;
        }

        set_transient('apreas_gcal_json_erro_' . get_current_user_id(), 'Chave JSON inválida: o conteúdo precisa ser o arquivo completo da service account, com client_email e private_key. O valor anterior foi mantido.', 60);

        return get_option('apreas_gcal_service_json', '');
    }

    public function avisos_google()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $erro = get_transient('apreas_gcal_json_erro_' . get_current_user_id());
        if (!$erro) {
            return;
        }

        delete_transient('apreas_gcal_json_erro_' . get_current_user_id());
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($erro) . '</p></div>';
    }

    public function enqueue_gcal_assets($hook)
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $tela = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$tela || strpos($tela->id, 'apreas-settings') === false) {
            return;
        }

        wp_enqueue_script(
            'apreas-gcal-settings',
            plugins_url('/admin/js/gcal-settings.js', dirname(__DIR__) . '/apreas.php'),
            [],
            '1.0.0',
            true
        );

        $gcal = \Apreas\GoogleCalendar::getInstance();
        wp_localize_script('apreas-gcal-settings', 'ApreasGcal', [
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('apreas_gcal_ajax'),
            'userEmail'   => wp_get_current_user()->user_email,
            'configurado' => $gcal->configurado(),
            'ultimoTeste' => $gcal->ultimo_teste(),
        ]);
    }

    /**
     * Os dois endpoints AJAX do assistente Google Calendar exigem manage_options
     * e nonce: a chave JSON da service account só é lida por administradores.
     */
    private function gcal_ajax_permitido()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'codigo'   => 'acesso_negado',
                'mensagem' => 'Acesso negado: esta seção é restrita a administradores.',
            ], 403);
        }

        check_ajax_referer('apreas_gcal_ajax', 'nonce');
    }

    public function ajax_testar_conexao()
    {
        $this->gcal_ajax_permitido();

        // A chave JSON não passa por sanitize_text_field: o private_key tem
        // sequências \n que seriam destruídas.
        $json        = isset($_POST['json']) ? (string) wp_unslash($_POST['json']) : '';
        $calendar_id = isset($_POST['calendar_id']) ? sanitize_text_field(wp_unslash($_POST['calendar_id'])) : '';

        $gcal      = \Apreas\GoogleCalendar::getInstance();
        $resultado = $gcal->testar_com($json, $calendar_id);

        if (is_wp_error($resultado)) {
            wp_send_json_error([
                'codigo'   => $resultado->get_error_code(),
                'mensagem' => $resultado->get_error_message(),
            ]);
        }

        $gcal->registrar_teste($resultado);
        $resultado['ultimoTeste'] = $gcal->ultimo_teste();

        wp_send_json_success($resultado);
    }

    public function ajax_salvar_gcal()
    {
        $this->gcal_ajax_permitido();

        $json        = trim((string) (isset($_POST['json']) ? wp_unslash($_POST['json']) : ''));
        $calendar_id = isset($_POST['calendar_id']) ? sanitize_text_field(wp_unslash($_POST['calendar_id'])) : '';
        $enabled     = !empty($_POST['enabled']) ? 1 : 0;

        $client_email = '';
        if ($json !== '') {
            $chave = json_decode($json, true);
            if (!is_array($chave) || empty($chave['client_email']) || empty($chave['private_key'])) {
                wp_send_json_error([
                    'codigo'   => 'json_invalido',
                    'mensagem' => 'Chave JSON inválida: nada foi salvo. Cole o arquivo completo da service account, com client_email e private_key.',
                ]);
            }
            $client_email = sanitize_email($chave['client_email']);
        }

        $gcal   = \Apreas\GoogleCalendar::getInstance();
        $mudou  = $json !== (string) get_option(\Apreas\GoogleCalendar::OPTION_SERVICE_JSON, '')
            || $calendar_id !== $gcal->calendar_id();

        update_option(\Apreas\GoogleCalendar::OPTION_SERVICE_JSON, $json);
        update_option(\Apreas\GoogleCalendar::OPTION_CALENDAR_ID, $calendar_id);
        update_option(\Apreas\GoogleCalendar::OPTION_ENABLED, $enabled);

        // Credenciais novas invalidam o teste anterior: some com o registro
        // para não exibir um "testado ✓" que não vale mais.
        if ($mudou) {
            $gcal->esquecer_teste();
        }

        wp_send_json_success([
            'mensagem'     => 'Configurações salvas.',
            'client_email' => $client_email,
            'configurado'  => $gcal->configurado(),
            'ultimoTeste'  => $gcal->ultimo_teste(),
            'quando'       => \Apreas\Fuso::agora(),
        ]);
    }

    private function campo_lista($nome, $rotulo, $descricao, $itens, $com_chave = false)
    {
        ?>
        <div class="apreas-setting-row" style="display: block;">
            <div class="apreas-setting-info" style="margin-bottom: 12px;">
                <strong><?php echo esc_html($rotulo); ?></strong>
                <p><?php echo esc_html($descricao); ?></p>
            </div>
            <div class="apreas-repeater" data-repeater data-nome="<?php echo esc_attr($nome); ?>"<?php echo $com_chave ? ' data-chave="1"' : ''; ?>>
                <?php foreach ($itens as $item) : ?>
                    <?php
                    $texto = $com_chave ? $item['rotulo'] : $item;
                    $chave = $com_chave ? $item['chave'] : '';
                    ?>
                    <div class="apreas-repeater-linha">
                        <input type="text" name="<?php echo esc_attr($nome); ?>[]" value="<?php echo esc_attr($texto); ?>" class="regular-text" placeholder="Nova opção">
                        <?php if ($com_chave) : ?>
                            <input type="hidden" name="<?php echo esc_attr($nome); ?>_chave[]" value="<?php echo esc_attr($chave); ?>">
                        <?php endif; ?>
                        <button type="button" class="apreas-repeater-remover" data-remover title="Remover"><span class="dashicons dashicons-trash"></span></button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button button-secondary apreas-repeater-adicionar" data-adicionar>
                <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><span class="apreas-repeater-adicionar-rotulo">Adicionar opção</span>
            </button>
        </div>
        <?php
    }

    public function render_settings_page()
    {
        ?>
        <div class="wrap apreas-settings-wrap">
            <h1 class="wp-heading-inline">Configurações Apreas</h1>
            <hr class="wp-header-end">

            <?php settings_errors(); ?>

            <form method="post" action="options.php" class="apreas-settings-form">
                <?php
                settings_fields('apreas_settings_group');
                do_settings_sections('apreas_settings_group');
                ?>
                
                <div class="apreas-card">
                    <div class="apreas-card-header">
                        <span class="dashicons dashicons-cart"></span>
                        <h2>Módulo de Carrinho</h2>
                    </div>
                    
                    <div class="apreas-setting-row">
                        <div class="apreas-setting-info">
                            <strong>Mini Carrinho Flutuante</strong>
                            <p>Exibe um ícone flutuante com o resumo do carrinho no canto inferior da tela.</p>
                        </div>
                        <div class="apreas-setting-control">
                            <label class="apreas-switch">
                                <input type="hidden" name="apreas_minicart_enabled" value="0">
                                <input type="checkbox" name="apreas_minicart_enabled" value="1" <?php checked(1, get_option('apreas_minicart_enabled', 1)); ?>>
                                <span class="apreas-slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="apreas-card">
                    <div class="apreas-card-header">
                        <span class="dashicons dashicons-money-alt"></span>
                        <h2>Módulo de Taxas e Descontos</h2>
                    </div>
                    
                    <div class="apreas-setting-row">
                        <div class="apreas-setting-info">
                            <strong>Taxa de Entrega Fixa</strong>
                            <p>Adiciona uma taxa fixa no checkout para todos os pedidos.</p>
                            
                            <div class="apreas-setting-input-wrapper" style="margin-top: 12px; <?php echo get_option('apreas_taxa_fixa_enabled', 0) ? '' : 'display: none;'; ?>" id="wrapper_taxa_fixa_valor">
                                <label style="font-size: 12px; color: var(--apreas-text-muted); display: block; margin-bottom: 4px;">Valor da Taxa (R$)</label>
                                <input type="number" step="0.01" name="apreas_taxa_fixa_valor" value="<?php echo esc_attr(get_option('apreas_taxa_fixa_valor', '20.00')); ?>" class="regular-text" style="width: 100px; border-radius: 4px;">
                            </div>
                        </div>
                        <div class="apreas-setting-control">
                            <label class="apreas-switch">
                                <input type="hidden" name="apreas_taxa_fixa_enabled" value="0">
                                <input type="checkbox" name="apreas_taxa_fixa_enabled" value="1" id="apreas_taxa_fixa_enabled" <?php checked(1, get_option('apreas_taxa_fixa_enabled', 0)); ?>>
                                <span class="apreas-slider round"></span>
                            </label>
                        </div>
                    </div>

                    <script>
                        document.getElementById('apreas_taxa_fixa_enabled').addEventListener('change', function() {
                            document.getElementById('wrapper_taxa_fixa_valor').style.display = this.checked ? 'block' : 'none';
                        });
                    </script>

                    <div class="apreas-setting-row">
                        <div class="apreas-setting-info">
                            <strong>Cupom de Desconto Customizado</strong>
                            <p>Substitui o campo de cupom padrão do WooCommerce por uma versão otimizada no checkout.</p>
                        </div>
                        <div class="apreas-setting-control">
                            <label class="apreas-switch">
                                <input type="hidden" name="apreas_custom_coupon_enabled" value="0">
                                <input type="checkbox" name="apreas_custom_coupon_enabled" value="1" <?php checked(1, get_option('apreas_custom_coupon_enabled', 1)); ?>>
                                <span class="apreas-slider round"></span>
                            </label>
</div>
            </div>
        </div>

        <div class="apreas-card">
            <div class="apreas-card-header">
                <span class="dashicons dashicons-phone"></span>
                <h2>Identidade & Contato</h2>
            </div>

            <div class="apreas-setting-row">
                <div class="apreas-setting-info">
                    <strong>WhatsApp de Dúvidas</strong>
                    <p>Número exibido nos formulários de login ("Dúvidas e Problemas?"). Informe apenas dígitos com DDI, ex.: 5511939490911.</p>
                </div>
                <div class="apreas-setting-control">
                    <input type="text" name="apreas_whatsapp_numero" value="<?php echo esc_attr(get_option('apreas_whatsapp_numero', '5511939490911')); ?>" class="regular-text" style="max-width: 220px;" placeholder="5511939490911">
                </div>
            </div>

            <div class="apreas-setting-row">
                <div class="apreas-setting-info">
                    <strong>Cor do Botão de Acesso</strong>
                    <p>Cor principal usada nos botões e destaques dos formulários de login.</p>
                </div>
                <div class="apreas-setting-control">
                    <input type="color" name="apreas_primary_color" value="<?php echo esc_attr(get_option('apreas_primary_color', '#d32f2f')); ?>" style="width: 48px; height: 32px; padding: 2px; border: 1px solid var(--apreas-border); border-radius: 4px; cursor: pointer;">
                </div>
            </div>

            <div class="apreas-setting-row">
                <div class="apreas-setting-info">
                    <strong>Módulo de Login por Eventos</strong>
                    <p>Exibe o shortcode [login_form_eventos] e libera o acesso por participantes de eventos. Desative se o site for apenas de recordação escolar.</p>
                </div>
                <div class="apreas-setting-control">
                    <label class="apreas-switch">
                        <input type="hidden" name="apreas_eventos_enabled" value="0">
                        <input type="checkbox" name="apreas_eventos_enabled" value="1" <?php checked(1, get_option('apreas_eventos_enabled', 1)); ?>>
                        <span class="apreas-slider round"></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="apreas-card">
            <div class="apreas-card-header">
                <span class="dashicons dashicons-category"></span>
                <h2>Checkout Apreas</h2>
            </div>

            <div class="apreas-setting-row">
                <div class="apreas-setting-info">
                    <strong>Categoria de Produtos do Checkout</strong>
                    <p>Categoria usada para exibir os campos do aluno no checkout. Ao migrar para outro site, ajuste para a categoria criada lá.</p>
                </div>
                <div class="apreas-setting-control">
                    <select name="apreas_checkout_category" style="max-width: 260px;">
                        <?php
                        $cats = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
                        $cats = (is_array($cats) && !is_wp_error($cats)) ? $cats : array();
                        $slug_atual = get_option('apreas_checkout_category', 'recordacao-escolar');
                        $existe = false;
                        foreach ($cats as $cat) {
                            if ($cat->slug === $slug_atual) { $existe = true; break; }
                        }
                        if (!empty($slug_atual) && !$existe) {
                            echo '<option value="' . esc_attr($slug_atual) . '" selected>' . esc_html($slug_atual) . '</option>';
                        }
                        foreach ($cats as $cat) {
                            echo '<option value="' . esc_attr($cat->slug) . '"' . selected($cat->slug, $slug_atual, false) . '>' . esc_html($cat->name) . ' (' . esc_html($cat->slug) . ')</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <?php if (current_user_can('manage_options')) : ?>
        <div class="apreas-card">
            <div class="apreas-card-header">
                <span class="dashicons dashicons-list-view"></span>
                <h2>Listas de Eventos</h2>
            </div>
            <?php
            $this->campo_lista('apreas_eventos_tipos', 'Tipo de Evento', 'Opções do campo "Tipo de Evento" no cadastro de Eventos.', \Apreas\Eventos::tipos_evento());
            $this->campo_lista('apreas_eventos_entregas', 'Forma de Entrega', 'Opções do campo "Forma de Entrega" no cadastro de Eventos.', \Apreas\Eventos::formas_entrega());
            $this->campo_lista('apreas_eventos_pagamentos', 'Forma de Pagamento', 'Opções do campo "Forma de Pagamento" no cadastro de Eventos.', \Apreas\Eventos::formas_pagamento());
            $this->campo_lista('apreas_eventos_controle', 'Controle', 'Status ligáveis no widget "Controle" do Evento. Renomear o rótulo mantém o histórico dos eventos já salvos.', \Apreas\Eventos::controle_opcoes(), true);
            ?>
            <script>
                (function () {
                    function novaLinha(nome, comChave) {
                        var div = document.createElement('div');
                        div.className = 'apreas-repeater-linha';
                        var input = document.createElement('input');
                        input.type = 'text';
                        input.name = nome + '[]';
                        input.className = 'regular-text';
                        input.placeholder = 'Nova opção';
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'apreas-repeater-remover';
                        btn.setAttribute('data-remover', '');
                        btn.title = 'Remover';
                        btn.innerHTML = '<span class="dashicons dashicons-trash"></span>';
                        div.appendChild(input);
                        if (comChave) {
                            var hid = document.createElement('input');
                            hid.type = 'hidden';
                            hid.name = nome + '_chave[]';
                            hid.value = '';
                            div.appendChild(hid);
                        }
                        div.appendChild(btn);
                        return div;
                    }
                    document.addEventListener('click', function (e) {
                        var add = e.target.closest('[data-adicionar]');
                        if (add) {
                            var rep = add.parentNode.querySelector('[data-repeater]');
                            rep.appendChild(novaLinha(rep.getAttribute('data-nome'), rep.hasAttribute('data-chave')));
                            var inputs = rep.querySelectorAll('input[type=text]');
                            inputs[inputs.length - 1].focus();
                            return;
                        }
                        var rem = e.target.closest('[data-remover]');
                        if (rem) {
                            var linha = rem.closest('.apreas-repeater-linha');
                            linha.parentNode.removeChild(linha);
                        }
                    });
                })();
            </script>
        </div>
        <?php endif; ?>

        <?php if (current_user_can('manage_options')) : ?>
        <div class="apreas-card">
            <div class="apreas-card-header">
                <span class="dashicons dashicons-calendar-alt"></span>
                <h2>Google Calendar — Eventos</h2>
                <span class="apreas-gcal-status" data-status-geral></span>
            </div>

            <div class="apreas-setting-row">
                <div class="apreas-setting-info">
                    <strong>Envio automático</strong>
                    <p>Cria o evento no Google Calendar ao publicar um Evento e atualiza o mesmo evento a cada edição.</p>
                </div>
                <div class="apreas-setting-control">
                    <label class="apreas-switch">
                        <input type="hidden" name="apreas_gcal_enabled" value="0">
                        <input type="checkbox" name="apreas_gcal_enabled" value="1" id="apreas_gcal_enabled" <?php checked(1, get_option('apreas_gcal_enabled', 1)); ?>>
                        <span class="apreas-slider round"></span>
                    </label>
                </div>
            </div>

            <div class="apreas-gcal" id="apreas-gcal">
                <ol class="apreas-gcal-steps">
                    <li class="apreas-gcal-step" data-step="1">
                        <button type="button" class="apreas-gcal-step-botao" data-ir="1">
                            <span class="apreas-gcal-bolinha" data-bolinha="1">1</span>
                            <span class="apreas-gcal-step-rotulo">Chave JSON</span>
                        </button>
                    </li>
                    <li class="apreas-gcal-step" data-step="2">
                        <button type="button" class="apreas-gcal-step-botao" data-ir="2">
                            <span class="apreas-gcal-bolinha" data-bolinha="2">2</span>
                            <span class="apreas-gcal-step-rotulo">Calendar ID</span>
                        </button>
                    </li>
                    <li class="apreas-gcal-step" data-step="3">
                        <button type="button" class="apreas-gcal-step-botao" data-ir="3">
                            <span class="apreas-gcal-bolinha" data-bolinha="3">3</span>
                            <span class="apreas-gcal-step-rotulo">Testar e salvar</span>
                        </button>
                    </li>
                </ol>

                <section class="apreas-gcal-passo" data-passo="1">
                    <div class="apreas-gcal-passo-cab">
                        <strong>Chave JSON da Service Account</strong>
                        <span class="apreas-gcal-chip" data-chip="1">Pendente</span>
                    </div>
                    <p class="apreas-gcal-desc">Cole o conteúdo completo do arquivo .json baixado no Google Cloud. A chave fica salva no banco e esta seção só aparece para o seu usuário.</p>

                    <details class="apreas-gcal-guia">
                        <summary><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>Onde conseguir essa chave?</summary>
                        <div class="apreas-gcal-guia-corpo">
                            <ol>
                                <li>Acesse <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">console.cloud.google.com</a> e crie (ou selecione) um projeto.</li>
                                <li>Em <strong>APIs e serviços → Biblioteca</strong>, busque <strong>Google Calendar API</strong> e clique em <strong>Ativar</strong>.</li>
                                <li>Em <strong>IAM e administrador → Contas de serviço</strong>, crie uma conta (ex.: <code>apreas-eventos</code>).</li>
                                <li>Abra a conta criada → aba <strong>Chaves</strong> → <strong>Adicionar chave → Criar nova chave → JSON</strong>. O download do arquivo começa na hora.</li>
                                <li>Abra o arquivo .json, copie <strong>tudo</strong> (da primeira à última chave) e cole no campo abaixo.</li>
                            </ol>
                            <p class="apreas-gcal-guia-nota">Guarde o arquivo em local seguro: a chave dá acesso aos calendários compartilhados com ela.</p>
                        </div>
                    </details>

                    <textarea name="apreas_gcal_service_json" rows="7" class="apreas-gcal-json" data-json spellcheck="false" placeholder='{"type": "service_account", "client_email": "...", "private_key": "..."}'><?php echo esc_textarea(get_option('apreas_gcal_service_json', '')); ?></textarea>
                    <p class="apreas-gcal-feedback" data-feedback="1"><span class="dashicons dashicons-info" aria-hidden="true"></span><span data-feedback-texto="1">Aguardando a chave JSON.</span></p>

                    <div class="apreas-gcal-identidade" data-identidade hidden>
                        <div class="apreas-gcal-identidade-item">
                            <span class="apreas-gcal-identidade-rotulo">E-mail da service account</span>
                            <code data-client-email></code>
                        </div>
                        <button type="button" class="apreas-gcal-copiar" data-copiar="email"><span class="dashicons dashicons-clipboard" aria-hidden="true"></span><span data-copiar-texto>Copiar e-mail</span></button>
                        <div class="apreas-gcal-identidade-item">
                            <span class="apreas-gcal-identidade-rotulo">Projeto</span>
                            <code data-project-id></code>
                        </div>
                    </div>
                </section>

                <section class="apreas-gcal-passo" data-passo="2">
                    <div class="apreas-gcal-passo-cab">
                        <strong>Calendar ID</strong>
                        <span class="apreas-gcal-chip" data-chip="2">Pendente</span>
                    </div>
                    <p class="apreas-gcal-desc">Calendário que vai receber os eventos. Pode ser o principal (o ID é o e-mail da sua conta Google) ou um dedicado, com ID terminado em <code>@group.calendar.google.com</code>.</p>

                    <div class="apreas-gcal-idioma" role="note">
                        <span class="apreas-gcal-idioma-icone"><span class="dashicons dashicons-translation" aria-hidden="true"></span></span>
                        <div class="apreas-gcal-idioma-corpo">
                            <p class="apreas-gcal-idioma-titulo">Pode aparecer como <span class="apreas-gcal-nome">Google Calendário</span> ou <span class="apreas-gcal-nome">Google Agenda</span></p>
                            <p class="apreas-gcal-idioma-texto">É o mesmo serviço: o nome muda conforme o idioma da conta Google. Os menus também podem estar em inglês (Settings, Share with specific people, Integrate calendar).</p>
                        </div>
                    </div>

                    <details class="apreas-gcal-guia">
                        <summary><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>Como compartilhar o calendário e achar o ID</summary>
                        <div class="apreas-gcal-guia-corpo">
                            <ol>
                                <li>Abra <a href="https://calendar.google.com/" target="_blank" rel="noopener noreferrer">calendar.google.com</a> → engrenagem (topo direito) → <strong>Configurações</strong>.</li>
                                <li>Em <strong>Configurações dos meus calendários</strong>, clique no calendário que vai receber os eventos (ex.: o seu nome).</li>
                                <li>Em <strong>Compartilhar com pessoas específicas</strong> → <strong>Adicionar pessoas e grupos</strong>: cole o e-mail da service account (abaixo), escolha a permissão <strong>Fazer alterações nos eventos</strong> e clique em <strong>Enviar</strong>.</li>
                                <li>Em <strong>Integrar calendário</strong>, copie o <strong>Calendar ID</strong> e cole no campo abaixo.</li>
                            </ol>
                            <div class="apreas-gcal-email-servico" data-email-servico hidden>
                                <span>Compartilhe com:</span>
                                <code data-client-email-2></code>
                                <button type="button" class="apreas-gcal-copiar" data-copiar="email"><span class="dashicons dashicons-clipboard" aria-hidden="true"></span><span data-copiar-texto>Copiar</span></button>
                            </div>
                            <p class="apreas-gcal-guia-nota">Sem o compartilhamento com permissão de edição, o teste até conecta, mas o envio dos eventos falha.</p>
                        </div>
                    </details>

                    <div class="apreas-gcal-linha">
                        <input type="text" name="apreas_gcal_calendar_id" class="apreas-gcal-input" data-calendar-id placeholder="seu-email@gmail.com ou exemplo@group.calendar.google.com" value="<?php echo esc_attr(get_option('apreas_gcal_calendar_id', '')); ?>">
                        <button type="button" class="button button-secondary apreas-gcal-usar-email" data-usar-email>Usar meu e-mail</button>
                    </div>
                    <p class="apreas-gcal-feedback" data-feedback="2"><span class="dashicons dashicons-info" aria-hidden="true"></span><span data-feedback-texto="2">Aguardando o Calendar ID.</span></p>
                </section>

                <section class="apreas-gcal-passo" data-passo="3">
                    <div class="apreas-gcal-passo-cab">
                        <strong>Testar e salvar</strong>
                        <span class="apreas-gcal-chip" data-chip="3">Pendente</span>
                    </div>
                    <p class="apreas-gcal-desc">O teste usa os valores preenchidos acima, <strong>sem salvar e sem sair desta página</strong>: ele autentica com a chave e lê o calendário. Nenhum evento é criado.</p>

                    <div class="apreas-gcal-acoes">
                        <button type="button" class="button apreas-gcal-testar" data-testar disabled><span class="dashicons dashicons-update" aria-hidden="true" data-icone-testar></span><span data-testar-texto>Testar conexão</span></button>
                        <button type="button" class="button apreas-gcal-salvar" data-salvar><span class="dashicons dashicons-saved" aria-hidden="true" data-icone-salvar></span><span data-salvar-texto>Salvar configurações</span></button>
                        <span class="apreas-gcal-pendente" data-pendente><span class="dashicons dashicons-warning" aria-hidden="true"></span>Alterações não salvas</span>
                    </div>

                    <div class="apreas-gcal-resultado" data-resultado hidden></div>
                    <p class="apreas-gcal-ultimo" data-ultimo hidden><span class="dashicons dashicons-backup" aria-hidden="true"></span><span data-ultimo-texto></span></p>
                </section>
            </div>
        </div>
        <?php endif; ?>

        <div class="apreas-form-actions">
                    <?php submit_button('Salvar Alterações', 'primary large'); ?>
                </div>
            </form>
        </div>

        <style>
            :root {
                --apreas-primary: #D90D28;
                --apreas-bg: #f0f0f1;
                --apreas-card-bg: #ffffff;
                --apreas-text: #1d2327;
                --apreas-text-muted: #646970;
                --apreas-border: #dcdcde;
            }

            .apreas-settings-wrap {
                margin: 20px 20px 0 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }

            .apreas-settings-wrap h1 {
                font-weight: 600;
                margin-bottom: 20px;
                color: var(--apreas-text);
            }

            .apreas-card {
                background: var(--apreas-card-bg);
                border: 1px solid var(--apreas-border);
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                margin-bottom: 24px;
                overflow: hidden;
            }

            .apreas-card-header {
                padding: 16px 24px;
                border-bottom: 1px solid var(--apreas-border);
                display: flex;
                align-items: center;
                gap: 12px;
                background: #fcfcfc;
            }

            .apreas-card-header .dashicons {
                color: var(--apreas-primary);
                font-size: 20px;
                width: 20px;
                height: 20px;
            }

            .apreas-card-header h2 {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
                color: var(--apreas-text);
            }

            .apreas-setting-row {
                padding: 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                transition: background 0.2s ease;
            }

            .apreas-setting-row:hover {
                background: #f9f9f9;
            }

            .apreas-setting-info strong {
                display: block;
                font-size: 15px;
                margin-bottom: 4px;
                color: var(--apreas-text);
            }

            .apreas-setting-info p {
                margin: 0;
                color: var(--apreas-text-muted);
                font-size: 13px;
                line-height: 1.5;
            }

            /* Premium Toggle Switch */
            .apreas-switch {
                position: relative;
                display: inline-block;
                width: 48px;
                height: 24px;
            }

            .apreas-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .apreas-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #cbd5e0;
                transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .apreas-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            input:checked + .apreas-slider {
                background-color: var(--apreas-primary);
            }

            input:checked + .apreas-slider:before {
                transform: translateX(24px);
            }

            .apreas-slider.round {
                border-radius: 24px;
            }

            .apreas-slider.round:before {
                border-radius: 50%;
            }

            .apreas-repeater {
                display: flex;
                flex-direction: column;
                gap: 8px;
                max-width: 420px;
            }

            .apreas-repeater-linha {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .apreas-repeater-linha input[type=text] {
                flex: 1 1 auto;
                min-width: 0;
                border-radius: 4px;
            }

            .apreas-repeater-remover {
                flex: 0 0 auto;
                width: 32px;
                height: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                border: 1px solid var(--apreas-border);
                background: #fff;
                border-radius: 4px;
                cursor: pointer;
                color: #b32d2e;
                transition: background .15s ease, border-color .15s ease;
            }

            .apreas-repeater-remover:hover {
                background: #fcf0f1;
                border-color: #b32d2e;
            }

            .apreas-repeater-remover .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                line-height: 16px;
            }

            /* Especificidade acima de .wp-core-ui .button (margin:0 / display:inline-block / line-height) */
            .apreas-settings-wrap .apreas-repeater-adicionar {
                margin-top: 14px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                line-height: 1;
            }

            .apreas-settings-wrap .apreas-repeater-adicionar .dashicons {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
                width: 16px;
                height: 16px;
                line-height: 1;
            }

            .apreas-settings-wrap .apreas-repeater-adicionar-rotulo {
                display: inline-block;
                line-height: 1;
            }

            .apreas-form-actions {
                margin-top: 32px;
                padding-top: 20px;
                border-top: 1px solid var(--apreas-border);
            }

            .apreas-form-actions .button-primary {
                padding: 0 24px !important;
                height: 40px !important;
                line-height: 38px !important;
                font-size: 14px !important;
                font-weight: 500 !important;
                border-radius: 6px !important;
            }

            /* ===== Assistente Google Calendar ===== */
            .apreas-gcal-status {
                margin-left: auto;
                font-size: 11px;
                font-weight: 600;
                line-height: 1;
                padding: 5px 12px;
                border-radius: 999px;
                background: #f0f0f1;
                color: var(--apreas-text-muted);
                border: 1px solid var(--apreas-border);
                white-space: nowrap;
            }

            .apreas-gcal-status.is-configurado {
                background: #f0f6fc;
                border-color: #c5d9ed;
                color: #2271b1;
            }

            .apreas-gcal-status.is-testado {
                background: #f0f9f1;
                border-color: #b8e0b8;
                color: #007017;
            }

            .apreas-gcal {
                padding: 4px 24px 24px;
            }

            .apreas-gcal [hidden] {
                display: none !important;
            }

            .apreas-gcal-steps {
                display: flex;
                align-items: center;
                list-style: none;
                margin: 16px 0 4px;
                padding: 0;
            }

            .apreas-gcal-step {
                display: flex;
                align-items: center;
                flex: 1 1 0;
                min-width: 0;
            }

            .apreas-gcal-step:not(:last-child)::after {
                content: "";
                flex: 1 1 auto;
                height: 2px;
                margin: 0 12px;
                border-radius: 2px;
                background: var(--apreas-border);
                transition: background .25s ease;
            }

            .apreas-gcal-step.is-done:not(:last-child)::after {
                background: #7cc47f;
            }

            .apreas-gcal-step-botao {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 4px 6px;
                margin: 0;
                border: 0;
                background: transparent;
                cursor: pointer;
                border-radius: 6px;
                font: inherit;
            }

            .apreas-gcal-step-botao:focus-visible {
                outline: 2px solid var(--apreas-primary);
                outline-offset: 2px;
            }

            .apreas-gcal-bolinha {
                flex: 0 0 auto;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                border: 2px solid var(--apreas-border);
                background: #fff;
                color: var(--apreas-text-muted);
                font-size: 13px;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: border-color .2s ease, background .2s ease, color .2s ease, box-shadow .2s ease;
            }

            .apreas-gcal-bolinha .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                line-height: 16px;
            }

            .apreas-gcal-step-rotulo {
                font-size: 12px;
                font-weight: 600;
                color: var(--apreas-text-muted);
                white-space: nowrap;
                transition: color .2s ease;
            }

            .apreas-gcal-step.is-active .apreas-gcal-bolinha {
                border-color: var(--apreas-primary);
                color: var(--apreas-primary);
                box-shadow: 0 0 0 3px rgba(217, 13, 40, .12);
            }

            .apreas-gcal-step.is-active .apreas-gcal-step-rotulo {
                color: var(--apreas-text);
            }

            .apreas-gcal-step.is-done .apreas-gcal-bolinha {
                border-color: #00a32a;
                background: #00a32a;
                color: #fff;
                box-shadow: none;
            }

            .apreas-gcal-step.is-done .apreas-gcal-step-rotulo {
                color: #007017;
            }

            .apreas-gcal-step.is-error .apreas-gcal-bolinha {
                border-color: #d63638;
                background: #d63638;
                color: #fff;
                box-shadow: none;
            }

            .apreas-gcal-step.is-error .apreas-gcal-step-rotulo {
                color: #b32d2e;
            }

            .apreas-gcal-step.is-aviso .apreas-gcal-bolinha {
                border-color: #dba617;
                background: #dba617;
                color: #fff;
                box-shadow: none;
            }

            .apreas-gcal-step.is-aviso .apreas-gcal-step-rotulo {
                color: #b26a00;
            }

            .apreas-gcal-passo {
                border: 1px solid var(--apreas-border);
                border-radius: 8px;
                background: #fff;
                padding: 18px 20px;
                margin-top: 14px;
                transition: border-color .2s ease, box-shadow .2s ease;
            }

            .apreas-gcal-passo:focus-within {
                border-color: rgba(217, 13, 40, .5);
                box-shadow: 0 0 0 3px rgba(217, 13, 40, .08);
            }

            .apreas-gcal-passo-cab {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .apreas-gcal-passo-cab strong {
                font-size: 14px;
                color: var(--apreas-text);
            }

            .apreas-gcal-chip {
                margin-left: auto;
                font-size: 11px;
                font-weight: 600;
                line-height: 1;
                padding: 4px 10px;
                border-radius: 999px;
                background: #f0f0f1;
                color: var(--apreas-text-muted);
                white-space: nowrap;
                transition: background .2s ease, color .2s ease;
            }

            .apreas-gcal-chip.is-ok {
                background: #edf7ed;
                color: #007017;
            }

            .apreas-gcal-chip.is-erro {
                background: #fcf0f1;
                color: #b32d2e;
            }

            .apreas-gcal-chip.is-aviso {
                background: #fcf9e8;
                color: #b26a00;
            }

            .apreas-gcal-desc {
                margin: 6px 0 0;
                font-size: 12.5px;
                line-height: 1.55;
                color: var(--apreas-text-muted);
            }

            .apreas-gcal-desc code,
            .apreas-gcal-guia-corpo code {
                font-size: 11.5px;
                background: #f0f0f1;
                padding: 1px 5px;
                border-radius: 4px;
            }

            .apreas-gcal-idioma {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                margin: 12px 0;
                padding: 12px 14px;
                border: 1px solid #c5d9ed;
                border-left: 3px solid #2271b1;
                border-radius: 6px;
                background: linear-gradient(135deg, #f0f6fc 0%, #f8fbff 100%);
            }

            .apreas-gcal-idioma-icone {
                flex: 0 0 auto;
                width: 30px;
                height: 30px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: #2271b1;
                color: #fff;
                box-shadow: 0 2px 6px rgba(34, 113, 177, .3);
            }

            .apreas-gcal-idioma-icone .dashicons {
                font-size: 17px;
                width: 17px;
                height: 17px;
                line-height: 17px;
            }

            .apreas-gcal-idioma-corpo {
                min-width: 0;
                flex: 1 1 auto;
            }

            .apreas-gcal-idioma-titulo {
                margin: 0 0 4px;
                font-size: 12.5px;
                font-weight: 600;
                line-height: 1.7;
                color: #1d2327;
            }

            .apreas-gcal-nome {
                display: inline-block;
                padding: 1px 9px;
                border-radius: 999px;
                border: 1px solid #c5d9ed;
                background: #fff;
                color: #2271b1;
                font-size: 11.5px;
                font-weight: 700;
                white-space: nowrap;
            }

            .apreas-gcal-idioma-texto {
                margin: 0;
                font-size: 12px;
                line-height: 1.55;
                color: #3c434a;
            }

            .apreas-gcal-guia {
                margin: 12px 0;
                border: 1px dashed #c3c4c7;
                border-radius: 6px;
                background: #fcfcfc;
            }

            .apreas-gcal-guia summary {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 9px 12px;
                font-size: 12px;
                font-weight: 600;
                color: #2271b1;
                cursor: pointer;
                list-style: none;
                user-select: none;
            }

            .apreas-gcal-guia summary::-webkit-details-marker {
                display: none;
            }

            .apreas-gcal-guia summary:hover {
                color: #135e96;
            }

            .apreas-gcal-guia summary .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                line-height: 16px;
                transition: transform .2s ease;
            }

            .apreas-gcal-guia[open] summary .dashicons {
                transform: rotate(90deg);
            }

            .apreas-gcal-guia-corpo {
                padding: 2px 14px 12px;
                font-size: 12.5px;
                line-height: 1.55;
                color: #3c434a;
            }

            .apreas-gcal-guia-corpo ol {
                margin: 6px 0 0 20px;
            }

            .apreas-gcal-guia-corpo li {
                margin-bottom: 5px;
            }

            .apreas-gcal-guia-corpo a {
                color: #2271b1;
                font-weight: 600;
            }

            .apreas-gcal-guia-nota {
                margin: 10px 0 0;
                padding: 8px 10px;
                background: #fcf9e8;
                border-left: 3px solid #dba617;
                border-radius: 0 4px 4px 0;
                font-size: 12px;
                color: #6d5a10;
            }

            .apreas-gcal-json {
                display: block;
                width: 100%;
                min-height: 150px;
                padding: 12px;
                font-family: Consolas, Monaco, "Courier New", monospace;
                font-size: 12px;
                line-height: 1.6;
                color: var(--apreas-text);
                background: #f8f9fa;
                border: 1px solid var(--apreas-border);
                border-radius: 6px;
                resize: vertical;
                scrollbar-width: thin;
                scrollbar-color: #c3c4c7 #f0f0f1;
                transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
            }

            .apreas-gcal-json:focus {
                background: #fff;
                border-color: var(--apreas-primary);
                box-shadow: 0 0 0 3px rgba(217, 13, 40, .12);
                outline: none;
            }

            .apreas-gcal-json::-webkit-scrollbar {
                width: 10px;
                height: 10px;
            }

            .apreas-gcal-json::-webkit-scrollbar-track {
                background: #f0f0f1;
                border-radius: 6px;
            }

            .apreas-gcal-json::-webkit-scrollbar-thumb {
                background: #c3c4c7;
                border-radius: 6px;
                border: 2px solid #f0f0f1;
            }

            .apreas-gcal-json::-webkit-scrollbar-thumb:hover {
                background: var(--apreas-primary);
            }

            .apreas-gcal-feedback {
                display: flex;
                align-items: flex-start;
                gap: 6px;
                margin: 8px 0 0;
                font-size: 12px;
                line-height: 1.5;
                color: var(--apreas-text-muted);
            }

            .apreas-gcal-feedback .dashicons {
                flex: 0 0 auto;
                font-size: 16px;
                width: 16px;
                height: 16px;
                line-height: 16px;
                margin-top: 1px;
            }

            .apreas-gcal-feedback.is-ok {
                color: #007017;
            }

            .apreas-gcal-feedback.is-erro {
                color: #b32d2e;
            }

            .apreas-gcal-identidade {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 10px 18px;
                margin-top: 12px;
                padding: 12px 14px;
                background: #f0f9f1;
                border: 1px solid #b8e0b8;
                border-radius: 6px;
            }

            .apreas-gcal-identidade-item {
                display: flex;
                flex-direction: column;
                gap: 3px;
                min-width: 0;
            }

            .apreas-gcal-identidade-rotulo {
                font-size: 10.5px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .4px;
                color: #4a7c50;
            }

            .apreas-gcal-identidade code {
                font-size: 12.5px;
                font-weight: 600;
                color: #1d2327;
                word-break: break-all;
            }

            .apreas-gcal-copiar {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 10px;
                border: 1px solid var(--apreas-border);
                border-radius: 5px;
                background: #fff;
                color: #2c3338;
                font-size: 11px;
                font-weight: 600;
                line-height: 1;
                cursor: pointer;
                transition: border-color .15s ease, color .15s ease, background .15s ease;
            }

            .apreas-gcal-copiar .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
                line-height: 14px;
            }

            .apreas-gcal-copiar:hover {
                border-color: var(--apreas-primary);
                color: var(--apreas-primary);
            }

            .apreas-gcal-copiar.is-copiado {
                border-color: #00a32a;
                color: #007017;
                background: #f0f9f1;
            }

            .apreas-gcal-email-servico {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
                margin-top: 10px;
                padding: 8px 10px;
                background: #f0f6fc;
                border: 1px solid #c5d9ed;
                border-radius: 5px;
                font-size: 12px;
                color: #1d2327;
            }

            .apreas-gcal-email-servico code {
                font-weight: 600;
                word-break: break-all;
            }

            .apreas-gcal-linha {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
            }

            .apreas-gcal-input {
                flex: 1 1 260px;
                min-width: 0;
                max-width: 460px;
                padding: 8px 12px;
                font-size: 13px;
                color: var(--apreas-text);
                background: #f8f9fa;
                border: 1px solid var(--apreas-border);
                border-radius: 6px;
                transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
            }

            .apreas-gcal-input:focus {
                background: #fff;
                border-color: var(--apreas-primary);
                box-shadow: 0 0 0 3px rgba(217, 13, 40, .12);
                outline: none;
            }

            .apreas-settings-wrap .apreas-gcal-usar-email {
                display: inline-flex;
                align-items: center;
                height: 35px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
            }

            .apreas-gcal-acoes {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 10px;
                margin-top: 12px;
            }

            .apreas-settings-wrap .apreas-gcal-testar,
            .apreas-settings-wrap .apreas-gcal-salvar {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                height: 38px;
                padding: 0 18px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 600;
                line-height: 1;
                transition: background .15s ease, border-color .15s ease, color .15s ease, opacity .15s ease;
            }

            .apreas-settings-wrap .apreas-gcal-testar {
                background: var(--apreas-primary);
                border: 1px solid var(--apreas-primary);
                color: #fff;
            }

            .apreas-settings-wrap .apreas-gcal-testar:hover:not(:disabled) {
                background: #b00b21;
                border-color: #b00b21;
                color: #fff;
            }

            .apreas-settings-wrap .apreas-gcal-testar:disabled {
                opacity: .45;
                cursor: not-allowed;
            }

            .apreas-settings-wrap .apreas-gcal-salvar {
                background: #fff;
                border: 1px solid #c3c4c7;
                color: #2c3338;
            }

            .apreas-settings-wrap .apreas-gcal-salvar:hover:not(:disabled) {
                border-color: var(--apreas-primary);
                color: var(--apreas-primary);
            }

            .apreas-settings-wrap .apreas-gcal-salvar:disabled {
                opacity: .6;
                cursor: wait;
            }

            .apreas-settings-wrap .apreas-gcal-testar .dashicons,
            .apreas-settings-wrap .apreas-gcal-salvar .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                line-height: 16px;
            }

            .apreas-gcal-girar {
                animation: apreas-gcal-giro 1s linear infinite;
            }

            @keyframes apreas-gcal-giro {
                to { transform: rotate(360deg); }
            }

            .apreas-gcal-pendente {
                display: none;
                align-items: center;
                gap: 5px;
                font-size: 11.5px;
                font-weight: 600;
                color: #b26a00;
            }

            .apreas-gcal-pendente.is-visivel {
                display: inline-flex;
            }

            .apreas-gcal-pendente .dashicons {
                font-size: 15px;
                width: 15px;
                height: 15px;
                line-height: 15px;
            }

            .apreas-gcal-resultado {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                margin-top: 14px;
                padding: 14px 16px;
                border: 1px solid var(--apreas-border);
                border-radius: 6px;
                background: #f8f9fa;
                font-size: 13px;
                line-height: 1.55;
                animation: apreas-gcal-surge .25s ease;
            }

            @keyframes apreas-gcal-surge {
                from { opacity: 0; transform: translateY(4px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .apreas-gcal-resultado > .dashicons {
                flex: 0 0 auto;
                font-size: 20px;
                width: 20px;
                height: 20px;
                line-height: 20px;
                margin-top: 1px;
            }

            .apreas-gcal-resultado-corpo {
                min-width: 0;
                flex: 1 1 auto;
            }

            .apreas-gcal-resultado-corpo strong {
                display: block;
                font-size: 13.5px;
                margin-bottom: 2px;
            }

            .apreas-gcal-resultado-corpo p {
                margin: 0;
                font-size: 12.5px;
            }

            .apreas-gcal-resultado.is-testando {
                border-color: #c5d9ed;
                background: #f0f6fc;
            }

            .apreas-gcal-resultado.is-testando > .dashicons {
                color: #2271b1;
            }

            .apreas-gcal-resultado.is-ok {
                border-color: #b8e0b8;
                background: #f0f9f1;
            }

            .apreas-gcal-resultado.is-ok > .dashicons {
                color: #00a32a;
            }

            .apreas-gcal-resultado.is-aviso {
                border-color: #f0d8a8;
                background: #fcf9e8;
            }

            .apreas-gcal-resultado.is-aviso > .dashicons {
                color: #b26a00;
            }

            .apreas-gcal-resultado.is-erro {
                border-color: #f0c4c4;
                background: #fcf0f1;
            }

            .apreas-gcal-resultado.is-erro > .dashicons {
                color: #b32d2e;
            }

            .apreas-gcal-detalhes {
                display: grid;
                grid-template-columns: auto 1fr;
                gap: 3px 12px;
                margin: 10px 0 0;
                font-size: 12px;
            }

            .apreas-gcal-detalhes dt {
                color: var(--apreas-text-muted);
                font-weight: 400;
            }

            .apreas-gcal-detalhes dd {
                margin: 0;
                font-weight: 600;
                color: var(--apreas-text);
                word-break: break-all;
            }

            .apreas-gcal-dica {
                margin-top: 10px;
                padding: 9px 11px;
                background: rgba(255, 255, 255, .75);
                border-radius: 5px;
                font-size: 12px;
                color: #3c434a;
            }

            .apreas-gcal-dica strong {
                display: inline;
                font-size: 12px;
            }

            .apreas-gcal-ultimo {
                display: flex;
                align-items: center;
                gap: 6px;
                margin: 12px 0 0;
                font-size: 11.5px;
                color: var(--apreas-text-muted);
            }

            .apreas-gcal-ultimo .dashicons {
                font-size: 15px;
                width: 15px;
                height: 15px;
                line-height: 15px;
            }

            @media (max-width: 782px) {
                .apreas-gcal {
                    padding: 4px 14px 18px;
                }

                .apreas-gcal-steps {
                    flex-wrap: wrap;
                    gap: 6px;
                }

                .apreas-gcal-step {
                    flex: 0 1 auto;
                }

                .apreas-gcal-step:not(:last-child)::after {
                    display: none;
                }

                .apreas-gcal-passo {
                    padding: 14px;
                }

                .apreas-gcal-input {
                    max-width: none;
                }
            }
        </style>
        <?php
    }
}
