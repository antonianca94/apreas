<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

class Eventos {
    const POST_TYPE   = 'apreas_eventos';
    const NONCE_ACAO  = 'apreas_save_eventos';
    const NONCE_CAMPO = 'apreas_eventos_nonce';

    private static $instance;

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'registrar_post_type' ] );
        add_action( 'admin_init', [ $this, 'registrar_capacidades' ] );
        add_action( 'add_meta_boxes', [ $this, 'adicionar_meta_boxes' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ $this, 'salvar_campos' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'colunas_listagem' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'conteudo_coluna' ], 10, 2 );
        add_action( 'admin_notices', [ $this, 'avisos_editor' ] );
        add_action( 'admin_post_apreas_eventos_reenviar', [ $this, 'reenviar_ao_google' ] );
    }

    public static function tipos_evento() {
        return self::lista_opcoes( 'apreas_eventos_tipos', [ 'Casamento', 'Batizado', 'Aniversário', 'Ensaio', 'Crisma', 'Evento Esportivo', 'Ensaio de Natal' ] );
    }

    public static function formas_entrega() {
        return self::lista_opcoes( 'apreas_eventos_entregas', [ 'Banlek', 'Google Photos', 'Pendrive', 'Outro' ] );
    }

    public static function formas_pagamento() {
        return self::lista_opcoes( 'apreas_eventos_pagamentos', [ 'Dinheiro', 'Link de Pagamento', 'PIX', 'Via Site' ] );
    }

    /**
     * Lê a lista cadastrada em Configurações; usa o padrão enquanto nada foi salvo.
     * Uma lista salva vazia é respeitada (não volta ao padrão).
     */
    private static function lista_opcoes( $option, $padrao ) {
        $salva = get_option( $option, null );
        if ( is_array( $salva ) ) {
            return array_values( array_filter( $salva, function ( $v ) {
                return is_string( $v ) && $v !== '';
            } ) );
        }
        return $padrao;
    }

    /**
     * Itens de Controle como pares chave/rotulo. A chave é a meta_key estável:
     * renomear o rótulo não perde o histórico dos eventos já salvos.
     */
    public static function controle_opcoes() {
        $padrao = [
            [ 'chave' => 'foi_pago_cliente', 'rotulo' => 'Cliente Pagou?' ],
            [ 'chave' => 'terceiro_pago',    'rotulo' => 'Colaborador Pago?' ],
            [ 'chave' => 'editado',          'rotulo' => 'Fotos Editadas?' ],
            [ 'chave' => 'entregue',         'rotulo' => 'Entregue?' ],
        ];

        $salva = get_option( 'apreas_eventos_controle', null );
        if ( is_array( $salva ) ) {
            $itens = [];
            foreach ( $salva as $item ) {
                if ( is_array( $item ) && ! empty( $item['chave'] ) && ! empty( $item['rotulo'] ) ) {
                    $itens[] = [ 'chave' => (string) $item['chave'], 'rotulo' => (string) $item['rotulo'] ];
                }
            }
            return $itens;
        }

        return $padrao;
    }

    /**
     * O post type antigo "eventos" foi removido preservando os registros, e o
     * shortcode de login ainda consulta por ele. O slug novo evita esse conflito.
     */
    public function registrar_post_type() {
        $labels = [
            'name'               => 'Eventos',
            'singular_name'      => 'Evento',
            'menu_name'          => 'Eventos',
            'all_items'          => 'Todos os Eventos',
            'add_new'            => 'Adicionar novo',
            'add_new_item'       => 'Adicionar novo Evento',
            'edit_item'          => 'Editar Evento',
            'update_item'        => 'Atualizar Evento',
            'view_item'          => 'Ver Evento',
            'search_items'       => 'Procurar Eventos',
            'not_found'          => 'Nenhum evento encontrado',
            'not_found_in_trash' => 'Nenhum evento encontrado no lixo',
        ];

        register_post_type( self::POST_TYPE, [
            'labels'              => $labels,
            'description'         => 'Serviços e eventos com envio automático para o Google Calendar.',
            'menu_icon'           => 'dashicons-calendar-alt',
            'supports'            => [ 'title' ],
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'apreas-relatorio',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'has_archive'         => false,
            'rewrite'             => false,
            'can_export'          => true,
            'capability_type'     => 'post',
            'capabilities'        => [
                'edit_post'              => 'edit_apreas_eventos',
                'read_post'              => 'read_apreas_eventos',
                'delete_post'            => 'delete_apreas_eventos',
                'edit_posts'             => 'edit_apreas_eventos',
                'edit_others_posts'      => 'manage_apreas_eventos',
                'publish_posts'          => 'manage_apreas_eventos',
                'create_posts'           => 'create_apreas_eventos',
                'read_private_posts'     => 'read',
                'delete_posts'           => 'manage_apreas_eventos',
                'delete_private_posts'   => 'manage_apreas_eventos',
                'delete_published_posts' => 'manage_apreas_eventos',
                'delete_others_posts'    => 'manage_apreas_eventos',
                'edit_private_posts'     => 'edit_apreas_eventos',
                'edit_published_posts'   => 'edit_apreas_eventos',
            ],
        ] );
    }

    public function registrar_capacidades() {
        $administrator = get_role( 'administrator' );
        if ( ! $administrator ) {
            return;
        }

        foreach ( [ 'edit_apreas_eventos', 'read_apreas_eventos', 'delete_apreas_eventos', 'create_apreas_eventos', 'manage_apreas_eventos' ] as $cap ) {
            if ( ! $administrator->has_cap( $cap ) ) {
                $administrator->add_cap( $cap );
            }
        }
    }

    public function enqueue_assets( $hook ) {
        $tela_atual = get_current_screen();
        if ( ! $tela_atual || $tela_atual->post_type !== self::POST_TYPE ) {
            return;
        }

        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        wp_enqueue_script(
            'apreas-eventos',
            plugins_url( '/admin/js/eventos.js', dirname( __DIR__ ) . '/apreas.php' ),
            [],
            '1.0.0',
            true
        );
    }

    public function adicionar_meta_boxes() {
        add_meta_box( 'apreas-eventos-dados', 'Dados do Evento', [ $this, 'metabox_dados' ], self::POST_TYPE, 'normal', 'high' );
        add_meta_box( 'apreas-eventos-valores', 'Valores', [ $this, 'metabox_valores' ], self::POST_TYPE, 'normal', 'default' );
        add_meta_box( 'apreas-eventos-controle', 'Controle', [ $this, 'metabox_controle' ], self::POST_TYPE, 'side', 'default' );
        add_meta_box( 'apreas-eventos-google', 'Google Calendar', [ $this, 'metabox_google' ], self::POST_TYPE, 'side', 'default' );
    }

    public function metabox_dados( $post ) {
        wp_nonce_field( self::NONCE_ACAO, self::NONCE_CAMPO );

        $data = get_post_meta( $post->ID, 'data', true );
        if ( $data && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data ) ) {
            $data = date( 'd/m/Y', strtotime( $data ) );
        }

        $horario_inicio   = get_post_meta( $post->ID, 'horario_inicio', true );
        $quantidade_horas = get_post_meta( $post->ID, 'quantidade_horas', true );
        $duracao_horas    = '';
        $duracao_minutos  = '';
        if ( $quantidade_horas !== '' ) {
            $total_minutos   = (int) round( (float) $quantidade_horas * 60 );
            $duracao_horas   = (string) intdiv( $total_minutos, 60 );
            $duracao_minutos = (string) ( $total_minutos % 60 );
        }
        $tipo_evento      = get_post_meta( $post->ID, 'tipo_evento', true );
        $local_evento     = get_post_meta( $post->ID, 'local_evento', true );
        $cliente          = get_post_meta( $post->ID, 'cliente_responsavel', true );
        $nome_fotografado = get_post_meta( $post->ID, 'nome_fotografado', true );
        $idade            = get_post_meta( $post->ID, 'idade', true );
        $forma_entrega    = get_post_meta( $post->ID, 'forma_entrega', true );
        $forma_pagamento  = get_post_meta( $post->ID, 'forma_pagamento', true );
        $observacoes      = get_post_meta( $post->ID, 'observacoes', true );
        ?>
        <style>
            .apreas-eventos-form .input-group { flex-wrap: nowrap; }
            .apreas-eventos-form .input-group > .form-control,
            .apreas-eventos-form .input-group .input-group-text {
                height: 38px;
                min-height: 38px;
                padding: 6px 12px;
                font-size: 14px;
                line-height: 1.5;
                border: 1px solid #ced4da;
                border-radius: 6px;
                background-color: #f8f9fa;
                box-shadow: none;
                color: #2c3338;
            }
            .apreas-eventos-form .input-group > .form-control { border-radius: 6px 0 0 6px; }
            .apreas-eventos-form .input-group > .input-group-prepend > .input-group-text,
            .apreas-eventos-form .input-group > .input-group-append > .input-group-text { background-color: transparent; }
            .apreas-eventos-form .input-group > .input-group-prepend > .input-group-text { border-radius: 6px 0 0 6px; }
            .apreas-eventos-form .input-group > .input-group-append > .input-group-text { border-radius: 0 6px 6px 0; }
            .apreas-eventos-form .input-group > .input-group-prepend + .form-control { border-radius: 0 6px 6px 0; }
            .apreas-eventos-form .input-group > .form-control:focus { border-color: #86b7fe; box-shadow: 0 0 0 .2rem rgba(13,110,253,.15); outline: none; }
            .apreas-eventos-form .input-group > select.form-control { max-width: none; }
            .apreas-eventos-form .input-group > textarea.form-control { height: auto; min-height: 96px; }
            .apreas-eventos-form .input-group-text .dashicons { font-size: 18px; width: 18px; height: 18px; line-height: 18px; color: #d90d28; }
            .apreas-eventos-form .input-group > .input-group-prepend > .input-group-text.apreas-moeda {
                display: inline-flex;
                align-items: center;
                background-color: #e6f4ea;
                color: #146c43;
                font-weight: 600;
                letter-spacing: .2px;
            }
            .apreas-eventos-form .input-group > .input-group-prepend + .form-control.apreas-valor { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }
            .apreas-eventos-form .input-group > .form-control.apreas-valor[readonly] { background-color: #f8f9fa; color: #146c43; }
            .apreas-eventos-form .input-group.apreas-duracao > .form-control { flex: 1 1 0; min-width: 0; border-radius: 0; text-align: center; font-weight: 600; font-variant-numeric: tabular-nums; }
            .apreas-eventos-form .input-group.apreas-duracao > .form-control:first-child { border-radius: 6px 0 0 6px; }
            .apreas-eventos-form .input-group.apreas-duracao > .input-group-append > .input-group-text { border-radius: 0; background-color: transparent; color: #646970; font-weight: 600; font-size: 12px; padding: 6px 8px; }
            .apreas-eventos-form .input-group.apreas-duracao > .input-group-append:last-child > .input-group-text { border-radius: 0 6px 6px 0; }
        </style>
        <div class="apreas-eventos-form">
        <div class="row mt-4 mb-4">
            <div class="col">
                <div class="form-group">
                    <label for="data" class="mb-2 fw-bold">Data</label>
                    <div class="input-group">
                        <input type="text" id="data" name="data" class="form-control" placeholder="dd/mm/aaaa" maxlength="10" value="<?php echo esc_attr( $data ); ?>" />
                        <?php $this->icone_grupo( 'dashicons-calendar-alt' ); ?>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="horario_inicio" class="mb-2 fw-bold">Horário Início</label>
                    <div class="input-group">
                        <input type="text" id="horario_inicio" name="horario_inicio" class="form-control" placeholder="hh:mm" maxlength="5" value="<?php echo esc_attr( $horario_inicio ); ?>" />
                        <?php $this->icone_grupo( 'dashicons-clock' ); ?>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="quantidade_horas" class="mb-2 fw-bold">Quantidade de Horas</label>
                    <div class="input-group apreas-duracao">
                        <input type="number" id="quantidade_horas" name="quantidade_horas" class="form-control" min="0" step="1" inputmode="numeric" placeholder="0" aria-label="Horas" value="<?php echo esc_attr( $duracao_horas ); ?>" />
                        <div class="input-group-append"><span class="input-group-text apreas-sufixo">h</span></div>
                        <input type="number" id="quantidade_minutos" name="quantidade_minutos" class="form-control" min="0" step="5" inputmode="numeric" placeholder="00" aria-label="Minutos" value="<?php echo esc_attr( $duracao_minutos ); ?>" />
                        <div class="input-group-append"><span class="input-group-text apreas-sufixo">min</span></div>
                        <?php $this->icone_grupo( 'dashicons-clock' ); ?>
                    </div>
                    <p id="apreas-duracao-resumo" style="margin-top:6px; margin-bottom:0; font-size:11px; color:#888;">▸ Horas e minutos definem a duração do evento no Google Calendar.</p>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col">
                <?php $this->campo_select( 'tipo_evento', 'Tipo de Evento', self::tipos_evento(), $tipo_evento, 'dashicons-tag' ); ?>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="local_evento" class="mb-2 fw-bold">Local do Evento</label>
                    <div class="input-group">
                        <input type="text" id="local_evento" name="local_evento" class="form-control" value="<?php echo esc_attr( $local_evento ); ?>" />
                        <?php $this->icone_grupo( 'dashicons-location' ); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col">
                <div class="form-group">
                    <label for="cliente_responsavel" class="mb-2 fw-bold">Cliente Responsável</label>
                    <div class="input-group">
                        <input type="text" id="cliente_responsavel" name="cliente_responsavel" class="form-control" value="<?php echo esc_attr( $cliente ); ?>" />
                        <?php $this->icone_grupo( 'dashicons-admin-users' ); ?>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="nome_fotografado" class="mb-2 fw-bold">Nome a ser Fotografado</label>
                    <div class="input-group">
                        <input type="text" id="nome_fotografado" name="nome_fotografado" class="form-control" value="<?php echo esc_attr( $nome_fotografado ); ?>" />
                        <?php $this->icone_grupo( 'dashicons-id-alt' ); ?>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="idade" class="mb-2 fw-bold">Idade</label>
                    <div class="input-group">
                        <input type="number" id="idade" name="idade" class="form-control" min="0" max="120" step="1" inputmode="numeric" placeholder="0" value="<?php echo esc_attr( $idade ); ?>" />
                        <?php $this->icone_grupo( 'dashicons-hourglass' ); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col">
                <?php $this->campo_select( 'forma_entrega', 'Forma de Entrega', self::formas_entrega(), $forma_entrega, 'dashicons-car' ); ?>
            </div>
            <div class="col">
                <?php $this->campo_select( 'forma_pagamento', 'Forma de Pagamento', self::formas_pagamento(), $forma_pagamento, 'dashicons-money-alt' ); ?>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col">
                <div class="form-group">
                    <label for="observacoes" class="mb-2 fw-bold">Descrição</label>
                    <div class="input-group">
                        <textarea id="observacoes" name="observacoes" rows="4" class="form-control"><?php echo esc_textarea( $observacoes ); ?></textarea>
                        <?php $this->icone_grupo( 'dashicons-edit' ); ?>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#data').flatpickr({
                    enableTime: false,
                    dateFormat: "d/m/Y",
                    time_24hr: false,
                    locale: 'pt'
                });
                $('#horario_inicio').flatpickr({
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true,
                    locale: 'pt'
                });
            });
        </script>
        <?php
    }

    public function metabox_valores( $post ) {
        $valor_pendrive = get_post_meta( $post->ID, 'valor_pendrive', true );
        $valor_total    = get_post_meta( $post->ID, 'valor_total', true );
        $valor_terceiro = get_post_meta( $post->ID, 'valor_terceiro', true );
        $valor_receber  = get_post_meta( $post->ID, 'valor_receber', true );
        ?>
        <div class="apreas-eventos-form">
        <div class="row mt-4 mb-4">
            <div class="col">
                <?php $this->campo_valor( 'valor_pendrive', 'Valor PenDrive', $valor_pendrive ); ?>
            </div>
            <div class="col">
                <?php $this->campo_valor( 'valor_total', 'Valor Total Cobrado', $valor_total ); ?>
            </div>
            <div class="col">
                <?php $this->campo_valor( 'valor_terceiro', 'Valor Terceiro', $valor_terceiro ); ?>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="valor_receber" class="mb-2 fw-bold">Valor a Receber</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text apreas-moeda">R$</span>
                        </div>
                        <input type="text" id="valor_receber" class="form-control apreas-valor" value="<?php echo esc_attr( $this->formatar_valor( $valor_receber ) ); ?>" readonly />
                    </div>
                    <p style="margin-top:6px; margin-bottom:0; font-size:11px; color:#888;">▸ Total Cobrado menos Valor Terceiro.</p>
                </div>
            </div>
        </div>
        </div>
        <?php
    }

    public function metabox_controle( $post ) {
        ?>
        <style>
            .apreas-eventos-controle { border: 1px solid #e2e4e7; border-radius: 8px; overflow: hidden; }
            .apreas-eventos-controle .apreas-controle-linha { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border-bottom: 1px solid #f0f0f1; cursor: pointer; margin: 0; transition: background .15s ease; }
            .apreas-eventos-controle .apreas-controle-linha:last-child { border-bottom: 0; }
            .apreas-eventos-controle .apreas-controle-rotulo { font-size: 13px; font-weight: 500; color: #2c3338; }
            .apreas-eventos-controle .apreas-switch { position: relative; display: inline-block; width: 40px; height: 22px; flex: 0 0 auto; }
            .apreas-eventos-controle .apreas-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
            .apreas-eventos-controle .apreas-slider { position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e0; border-radius: 22px; transition: .2s; }
            .apreas-eventos-controle .apreas-slider:before { content: ""; position: absolute; height: 16px; width: 16px; left: 3px; top: 3px; background: #fff; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,.15); transition: .2s; }
            .apreas-eventos-controle input:checked + .apreas-slider { background-color: #198754; }
            .apreas-eventos-controle input:checked + .apreas-slider:before { transform: translateX(18px); }
            .apreas-eventos-controle input:focus-visible + .apreas-slider { outline: 2px solid #86b7fe; outline-offset: 2px; }
            .apreas-eventos-controle .apreas-controle-linha:has(input:checked) { background: #f2faf5; }
            .apreas-eventos-controle .apreas-controle-linha:has(input:checked) .apreas-controle-rotulo { color: #146c43; font-weight: 600; }
        </style>
        <div class="apreas-eventos-controle">
            <?php foreach ( self::controle_opcoes() as $item ) : ?>
                <?php $valor = get_post_meta( $post->ID, $item['chave'], true ); ?>
                <label class="apreas-controle-linha">
                    <span class="apreas-controle-rotulo"><?php echo esc_html( $item['rotulo'] ); ?></span>
                    <span class="apreas-switch">
                        <input type="checkbox" name="<?php echo esc_attr( $item['chave'] ); ?>" value="1" <?php checked( $valor, '1' ); ?> />
                        <span class="apreas-slider"></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public function metabox_google( $post ) {
        $google   = GoogleCalendar::getInstance();
        $status   = get_post_meta( $post->ID, GoogleCalendar::META_STATUS, true );
        $erro     = get_post_meta( $post->ID, GoogleCalendar::META_ERRO, true );
        $sync     = get_post_meta( $post->ID, GoogleCalendar::META_SYNC, true );
        $link     = get_post_meta( $post->ID, GoogleCalendar::META_LINK, true );
        $rotulos  = [
            'ok'        => [ 'Sincronizado', '#198754' ],
            'erro'      => [ 'Erro no envio', '#dc3545' ],
            'sem_dados' => [ 'Faltam data/horas', '#fd7e14' ],
            'off'       => [ 'Envio desativado', '#6c757d' ],
        ];
        $rotulo = isset( $rotulos[ $status ] ) ? $rotulos[ $status ] : [ 'Ainda não enviado', '#6c757d' ];
        ?>
        <p>
            <span style="display:inline-block;padding:3px 10px;border-radius:12px;color:#fff;font-size:12px;font-weight:600;background:<?php echo esc_attr( $rotulo[1] ); ?>">
                <?php echo esc_html( $rotulo[0] ); ?>
            </span>
        </p>
        <?php if ( $sync ) : ?>
            <p style="font-size:12px;color:#666;margin:0 0 8px;">Último envio: <?php echo esc_html( mysql2date( 'd/m/Y H:i', $sync ) ); ?></p>
        <?php endif; ?>
        <?php if ( $erro ) : ?>
            <p style="font-size:12px;color:#dc3545;margin:0 0 8px;"><?php echo esc_html( $erro ); ?></p>
        <?php endif; ?>
        <?php if ( $link ) : ?>
            <p style="margin:0 0 8px;"><a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener">Abrir no Google Calendar</a></p>
        <?php endif; ?>
        <?php if ( $google->configurado() ) : ?>
            <a class="button button-secondary" href="<?php echo esc_url( $this->url_reenvio( $post->ID ) ); ?>">Reenviar ao Google</a>
        <?php else : ?>
            <p style="font-size:12px;color:#666;margin:0;">Configure o Calendar ID e a chave JSON em <a href="<?php echo esc_url( admin_url( 'admin.php?page=apreas-settings' ) ); ?>">Configurações Apreas</a>.</p>
        <?php endif; ?>
        <?php
    }

    private function campo_select( $campo, $rotulo, $opcoes, $valor, $icone ) {
        ?>
        <div class="form-group">
            <label for="<?php echo esc_attr( $campo ); ?>" class="mb-2 fw-bold"><?php echo esc_html( $rotulo ); ?></label>
            <div class="input-group">
                <select id="<?php echo esc_attr( $campo ); ?>" name="<?php echo esc_attr( $campo ); ?>" class="form-control">
                    <option value="">Selecione...</option>
                    <?php foreach ( $opcoes as $opcao ) : ?>
                        <option value="<?php echo esc_attr( $opcao ); ?>" <?php selected( $valor, $opcao ); ?>><?php echo esc_html( $opcao ); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php $this->icone_grupo( $icone ); ?>
            </div>
        </div>
        <?php
    }

    private function icone_grupo( $icone ) {
        ?>
        <div class="input-group-append">
            <span class="input-group-text"><i class="dashicons <?php echo esc_attr( $icone ); ?>"></i></span>
        </div>
        <?php
    }

    private function campo_valor( $campo, $rotulo, $valor ) {
        ?>
        <div class="form-group">
            <label for="<?php echo esc_attr( $campo ); ?>" class="mb-2 fw-bold"><?php echo esc_html( $rotulo ); ?></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text apreas-moeda">R$</span>
                </div>
                <input type="text" id="<?php echo esc_attr( $campo ); ?>" name="<?php echo esc_attr( $campo ); ?>" class="form-control apreas-valor" inputmode="decimal" autocomplete="off" value="<?php echo esc_attr( $this->formatar_valor( $valor ) ); ?>" />
            </div>
        </div>
        <?php
    }

    public function salvar_campos( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;
        if ( ! isset( $_POST[ self::NONCE_CAMPO ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_CAMPO ] ) ), self::NONCE_ACAO ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, 'data', $this->normalizar_data( $this->post( 'data' ) ) );
        update_post_meta( $post_id, 'horario_inicio', $this->normalizar_hora( $this->post( 'horario_inicio' ) ) );
        update_post_meta( $post_id, 'quantidade_horas', $this->normalizar_duracao( $this->post( 'quantidade_horas' ), $this->post( 'quantidade_minutos' ) ) );
        update_post_meta( $post_id, 'local_evento', sanitize_text_field( $this->post( 'local_evento' ) ) );
        update_post_meta( $post_id, 'cliente_responsavel', sanitize_text_field( $this->post( 'cliente_responsavel' ) ) );
        update_post_meta( $post_id, 'nome_fotografado', sanitize_text_field( $this->post( 'nome_fotografado' ) ) );
        update_post_meta( $post_id, 'idade', $this->normalizar_idade( $this->post( 'idade' ) ) );
        update_post_meta( $post_id, 'observacoes', sanitize_textarea_field( $this->post( 'observacoes' ) ) );

        update_post_meta( $post_id, 'tipo_evento', $this->valor_da_lista( $this->post( 'tipo_evento' ), self::tipos_evento() ) );
        update_post_meta( $post_id, 'forma_entrega', $this->valor_da_lista( $this->post( 'forma_entrega' ), self::formas_entrega() ) );
        update_post_meta( $post_id, 'forma_pagamento', $this->valor_da_lista( $this->post( 'forma_pagamento' ), self::formas_pagamento() ) );

        foreach ( self::controle_opcoes() as $item ) {
            $campo = $item['chave'];
            update_post_meta( $post_id, $campo, isset( $_POST[ $campo ] ) ? '1' : '0' );
        }

        $valor_total    = $this->normalizar_valor( $this->post( 'valor_total' ) );
        $valor_terceiro = $this->normalizar_valor( $this->post( 'valor_terceiro' ) );

        update_post_meta( $post_id, 'valor_pendrive', $this->normalizar_valor( $this->post( 'valor_pendrive' ) ) );
        update_post_meta( $post_id, 'valor_total', $valor_total );
        update_post_meta( $post_id, 'valor_terceiro', $valor_terceiro );
        update_post_meta( $post_id, 'valor_receber', $this->calcular_receber( $valor_total, $valor_terceiro ) );
    }

    private function post( $campo ) {
        return isset( $_POST[ $campo ] ) ? wp_unslash( $_POST[ $campo ] ) : '';
    }

    private function valor_da_lista( $valor, $opcoes ) {
        $valor = sanitize_text_field( $valor );
        return in_array( $valor, $opcoes, true ) ? $valor : '';
    }

    private function normalizar_data( $valor ) {
        $valor = sanitize_text_field( $valor );

        if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $partes ) ) {
            return checkdate( (int) $partes[2], (int) $partes[3], (int) $partes[1] ) ? $valor : '';
        }

        if ( preg_match( '/^(\d{2})\/(\d{2})\/(\d{4})$/', $valor, $partes ) && checkdate( (int) $partes[2], (int) $partes[1], (int) $partes[3] ) ) {
            return $partes[3] . '-' . $partes[2] . '-' . $partes[1];
        }

        return '';
    }

    private function normalizar_hora( $valor ) {
        $valor = sanitize_text_field( $valor );
        return preg_match( '/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $valor, $partes )
            ? $partes[1] . ':' . $partes[2]
            : '';
    }

    private function normalizar_duracao( $horas, $minutos ) {
        $horas   = sanitize_text_field( $horas );
        $minutos = sanitize_text_field( $minutos );
        if ( $horas === '' && $minutos === '' ) {
            return '';
        }
        $h = is_numeric( $horas ) ? max( 0, (int) $horas ) : 0;
        $m = is_numeric( $minutos ) ? max( 0, (int) $minutos ) : 0;
        $total = $h * 60 + $m;
        if ( $total < 1 ) {
            return '';
        }
        return number_format( $total / 60, 2, '.', '' );
    }

    private function normalizar_idade( $valor ) {
        $valor = sanitize_text_field( $valor );
        if ( $valor === '' || ! is_numeric( $valor ) ) {
            return '';
        }
        $idade = (int) $valor;
        return ( $idade >= 0 && $idade <= 120 ) ? (string) $idade : '';
    }

    private function normalizar_valor( $valor ) {
        $valor = preg_replace( '/[^0-9,.\-]/', '', sanitize_text_field( $valor ) );

        if ( strpos( $valor, ',' ) !== false ) {
            $valor = str_replace( '.', '', $valor );
            $valor = str_replace( ',', '.', $valor );
        }

        return is_numeric( $valor ) ? number_format( (float) $valor, 2, '.', '' ) : '';
    }

    private function calcular_receber( $total, $terceiro ) {
        if ( $total === '' && $terceiro === '' ) {
            return '';
        }
        return number_format( (float) $total - (float) $terceiro, 2, '.', '' );
    }

    private function formatar_valor( $valor ) {
        return ( $valor === '' || $valor === null ) ? '' : number_format( (float) $valor, 2, ',', '.' );
    }

    public function colunas_listagem( $colunas ) {
        $novas = [];
        foreach ( $colunas as $chave => $rotulo ) {
            if ( $chave === 'date' ) {
                $novas['apreas_gcal'] = 'Google Calendar';
            }
            $novas[ $chave ] = $rotulo;
        }
        return $novas;
    }

    public function conteudo_coluna( $coluna, $post_id ) {
        if ( $coluna !== 'apreas_gcal' ) {
            return;
        }

        $status = get_post_meta( $post_id, GoogleCalendar::META_STATUS, true );
        $rotulos = [
            'ok'        => [ 'Sincronizado', '#198754' ],
            'erro'      => [ 'Erro', '#dc3545' ],
            'sem_dados' => [ 'Faltam data/horas', '#fd7e14' ],
            'off'       => [ 'Desativado', '#6c757d' ],
        ];
        $rotulo = isset( $rotulos[ $status ] ) ? $rotulos[ $status ] : [ 'Não enviado', '#6c757d' ];

        printf(
            '<span style="display:inline-block;padding:2px 9px;border-radius:12px;color:#fff;font-size:11px;font-weight:600;background:%1$s">%2$s</span>',
            esc_attr( $rotulo[1] ),
            esc_html( $rotulo[0] )
        );
    }

    public function avisos_editor() {
        $tela = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $tela || $tela->post_type !== self::POST_TYPE || $tela->base !== 'post' ) {
            return;
        }

        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        if ( isset( $_GET['apreas_gcal_reenviado'] ) ) {
            $status = get_post_meta( $post_id, GoogleCalendar::META_STATUS, true );
            if ( $status === 'ok' ) {
                echo '<div class="notice notice-success is-dismissible"><p>Evento enviado ao Google Calendar.</p></div>';
            } else {
                $erro = get_post_meta( $post_id, GoogleCalendar::META_ERRO, true );
                echo '<div class="notice notice-error"><p><strong>Google Calendar:</strong> ' . esc_html( $erro ? $erro : 'não foi possível enviar o evento.' ) . '</p></div>';
            }
            return;
        }

        $status = get_post_meta( $post_id, GoogleCalendar::META_STATUS, true );
        if ( $status === 'erro' ) {
            $erro = get_post_meta( $post_id, GoogleCalendar::META_ERRO, true );
            echo '<div class="notice notice-error"><p><strong>Google Calendar:</strong> ' . esc_html( $erro ) . '</p></div>';
        } elseif ( $status === 'sem_dados' ) {
            echo '<div class="notice notice-warning"><p><strong>Google Calendar:</strong> preencha Data, Horário Início e Quantidade de Horas para enviar o evento.</p></div>';
        }
    }

    private function url_reenvio( $post_id ) {
        return wp_nonce_url(
            admin_url( 'admin-post.php?action=apreas_eventos_reenviar&post_id=' . absint( $post_id ) ),
            'apreas_eventos_reenviar_' . absint( $post_id )
        );
    }

    public function reenviar_ao_google() {
        $post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

        if ( ! $post_id || get_post_type( $post_id ) !== self::POST_TYPE ) {
            wp_die( 'Acesso negado.', '', [ 'response' => 403 ] );
        }

        check_admin_referer( 'apreas_eventos_reenviar_' . $post_id );

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( 'Acesso negado.', '', [ 'response' => 403 ] );
        }

        GoogleCalendar::getInstance()->sincronizar( $post_id );

        wp_safe_redirect( admin_url( 'post.php?post=' . $post_id . '&action=edit&apreas_gcal_reenviado=1' ) );
        exit;
    }
}