<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Campos extras do checkout WooCommerce — substitui plugin externo.
 */
class Checkout {

    private static $instance;

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'woocommerce_before_order_notes',                     [ $this, 'render_campos_checkout' ] );
        add_action( 'woocommerce_checkout_process',                       [ $this, 'validar_campos_checkout' ] );
        add_action( 'woocommerce_checkout_update_order_meta',             [ $this, 'salvar_campos_checkout' ] );
        add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'exibir_campos_admin' ], 10, 1 );
        add_filter( 'woocommerce_email_order_meta_fields',                [ $this, 'campos_no_email' ], 10, 3 );
        add_filter( 'woocommerce_admin_billing_fields',                   [ $this, 'adicionar_bairro_admin_billing' ] );
        add_filter( 'woocommerce_localisation_address_formats',           [ $this, 'formatar_endereco_br' ], 99 );
        add_filter( 'woocommerce_formatted_address_replacements',         [ $this, 'substituir_bairro_endereco' ], 10, 2 );
        add_filter( 'woocommerce_order_formatted_billing_address',        [ $this, 'adicionar_bairro_endereco_objeto' ], 10, 2 );
        add_filter( 'woocommerce_order_get_formatted_billing_address',    [ $this, 'adicionar_bairro_string_admin' ], 10, 3 );
        // Corrige mapeamento dos campos de endereço (bairro / cidade / estado)
        add_filter( 'woocommerce_checkout_fields',                        [ $this, 'corrigir_campos_endereco' ], 99 );

        // Adiciona taxa de entrega fixa
        add_action( 'woocommerce_cart_calculate_fees',                    [ $this, 'adicionar_taxa_entrega' ] );

        // Remove o bloco nativo de cupom do WooCommerce (Sempre oculto)
        remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
        add_action( 'wp_head', [ $this, 'esconder_cupom_nativo_css' ] );

        // Recursos de Cupom Customizado
        if ( get_option( 'apreas_custom_coupon_enabled', 1 ) ) {
            // Aplica o cupom nativo digitado no campo customizado
            add_action( 'woocommerce_checkout_update_order_review',           [ $this, 'aplicar_cupom_nativo_customizado' ] );

            // Injeta o feedback do cupom como fragment (mensagem inline)
            add_filter( 'woocommerce_update_order_review_fragments',          [ $this, 'fragment_cupom_feedback' ] );
        }
    }

    // ─────────────────────────────────────────────
    // CORREÇÃO — campos de endereço billing
    // ─────────────────────────────────────────────
    public function corrigir_campos_endereco( $fields ) {

        $billing = &$fields['billing'];

        // BAIRRO — cria o campo se não existir (não é nativo do WooCommerce)
        // ou corrige o tipo caso outro plugin já o tenha registrado errado
        $billing['billing_neighborhood'] = wp_parse_args(
            isset( $billing['billing_neighborhood'] ) ? $billing['billing_neighborhood'] : [],
            [
                'type'        => 'text',
                'label'       => __( 'Bairro', 'apreas' ),
                'required'    => true,
                'class'       => [ 'form-row-wide' ],
                'priority'    => 75,   // entre CEP (70) e Cidade (80)
                'clear'       => true,
            ]
        );
        // Força type=text independente do que outro plugin definiu
        $billing['billing_neighborhood']['type'] = 'text';

        // CIDADE — deve ser campo de texto aberto
        if ( isset( $billing['billing_city'] ) ) {
            $billing['billing_city']['type']     = 'text';
            $billing['billing_city']['label']    = __( 'Cidade', 'apreas' );
            $billing['billing_city']['priority'] = 80;
            $billing['billing_city']['class']    = [ 'form-row-wide' ];
        }

        // ESTADO — deve ser select (type = state)
        if ( isset( $billing['billing_state'] ) ) {
            $billing['billing_state']['type']     = 'state';
            $billing['billing_state']['label']    = __( 'Estado', 'apreas' );
            $billing['billing_state']['priority'] = 85;
            $billing['billing_state']['class']    = [ 'form-row-wide' ];
        }

        return $fields;
    }

    // ─────────────────────────────────────────────
    // TAXA DE ENTREGA FIXA
    // ─────────────────────────────────────────────
    public function adicionar_taxa_entrega( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        // Verifica se o módulo está habilitado nas configurações
        if ( ! get_option( 'apreas_taxa_fixa_enabled', 0 ) ) {
            return;
        }

        // Valor fixo do frete/taxa de entrega (exemplo: 20 reais)
        $valor_frete = 20.00;

        // Adiciona a taxa no final da compra
        $cart->add_fee( __( 'Taxa de Entrega', 'apreas' ), $valor_frete, false );
    }

    // ─────────────────────────────────────────────
    // APLICAR CUPOM NATIVO VIA CAMPO CUSTOMIZADO
    // ─────────────────────────────────────────────
    public function aplicar_cupom_nativo_customizado( $post_data_string ) {
        $post_data = [];
        parse_str( $post_data_string, $post_data );

        $codigo  = isset( $post_data['apreas_codigo_desconto'] ) ? sanitize_text_field( trim( $post_data['apreas_codigo_desconto'] ) ) : '';
        $remover = ! empty( $post_data['apreas_remover_cupom'] ) && $post_data['apreas_remover_cupom'] === '1';
        $codigo  = strtolower( $codigo );

        // Se o usuário clicou em Remover ou o campo está vazio, limpa os cupões
        if ( $remover || empty( $codigo ) ) {
            WC()->cart->remove_coupons();
            WC()->cart->calculate_totals();
            return;
        }

        // Evita re-aplicar o mesmo cupom se já está aplicado
        if ( WC()->cart->has_discount( $codigo ) ) {
            return;
        }

        // Remove qualquer cupom anterior e aplica o novo
        WC()->cart->remove_coupons();

        $coupon = new \WC_Coupon( $codigo );
        if ( $coupon->get_id() ) {
            // Suprime os avisos nativos do WooCommerce (usamos nosso feedback inline)
            add_filter( 'woocommerce_coupon_message', '__return_empty_string', 999 );
            add_filter( 'woocommerce_coupon_error',   '__return_empty_string', 999 );

            WC()->cart->apply_coupon( $codigo );

            remove_filter( 'woocommerce_coupon_message', '__return_empty_string', 999 );
            remove_filter( 'woocommerce_coupon_error',   '__return_empty_string', 999 );
            wc_clear_notices(); // limpa qualquer notice residual

            WC()->session->set( 'apreas_cupom_feedback', [
                'tipo'  => 'sucesso',
                'texto' => sprintf( __( 'Cupom <strong>%s</strong> aplicado com sucesso!', 'apreas' ), strtoupper( $codigo ) ),
            ] );
        } else {
            WC()->session->set( 'apreas_cupom_feedback', [
                'tipo'  => 'erro',
                'texto' => __( 'Cupom inválido ou não encontrado.', 'apreas' ),
            ] );
        }
        WC()->cart->calculate_totals();
    }

    // ─────────────────────────────────────────────
    // FRAGMENT — Feedback inline do cupom
    // ─────────────────────────────────────────────
    public function fragment_cupom_feedback( $fragments ) {
        $feedback = WC()->session->get( 'apreas_cupom_feedback' );

        $html = '<div id="apreas-cupom-feedback" style="margin-top:10px;">';
        if ( ! empty( $feedback['tipo'] ) ) {
            if ( $feedback['tipo'] === 'sucesso' ) {
                $html .= '<div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f0fdf4;border:1px solid #86efac;border-radius:6px;font-size:13px;color:#15803d;">';
                $html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';
                $html .= '<span>' . wp_kses_post( $feedback['texto'] ) . '</span>';
                $html .= '</div>';
            } else {
                $html .= '<div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;font-size:13px;color:#dc2626;">';
                $html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
                $html .= '<span>' . esc_html( $feedback['texto'] ) . '</span>';
                $html .= '</div>';
            }
            // Limpa após exibir
            WC()->session->set( 'apreas_cupom_feedback', null );
        }
        $html .= '</div>';

        $fragments['#apreas-cupom-feedback'] = $html;
        return $fragments;
    }

    // ─────────────────────────────────────────────
    // ESCONDER CAMPO NATIVO DE CUPOM VIA CSS
    // ─────────────────────────────────────────────
    public function esconder_cupom_nativo_css() {
        if ( ! is_checkout() ) return;
        echo '<style>
            .woocommerce-form-coupon-toggle,
            .woocommerce-form-coupon,
            .checkout_coupon,
            .woocommerce-remove-coupon { display: none !important; }

            /* Estilo premium para o botão Aplicar */
            #btn_apreas_aplicar_cupom {
                background-color: #D90D28 !important;
                color: #ffffff !important;
                transition: background-color 0.2s ease;
                border: none !important;
            }
            #btn_apreas_aplicar_cupom:hover {
                background-color: #b00b20 !important;
            }
        </style>';
    }

    // ─────────────────────────────────────────────
    // HELPER — Verifica se há produto da categoria
    // ─────────────────────────────────────────────
    private function tem_produto_recordacao_escolar() {
        if ( ! WC()->cart ) return false;
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( has_term( 'recordacao-escolar', 'product_cat', $cart_item['product_id'] ) ) {
                return true;
            }
        }
        return false;
    }

    // ─────────────────────────────────────────────
    // CHECKOUT — renderiza campos no formulário
    // ─────────────────────────────────────────────
    public function render_campos_checkout( $checkout ) {
        if ( ! $this->tem_produto_recordacao_escolar() ) {
            return;
        }

        echo '<div class="apreas-campos-aluno" style="margin-bottom:4rem;">';
        echo '<h3>' . esc_html__( 'Informações do Aluno', 'apreas' ) . '</h3>';

        woocommerce_form_field( 'apreas_aluno', [
            'type'     => 'text',
            'label'    => __( 'Nome Completo do(a) Aluno(a)', 'apreas' ),
            'required' => true,
            'class'    => [ 'form-row-wide' ],
        ], $checkout->get_value( 'apreas_aluno' ) );

        woocommerce_form_field( 'apreas_escola', [
            'type'     => 'text',
            'label'    => __( 'Escola', 'apreas' ),
            'required' => true,
            'class'    => [ 'form-row-first' ],
        ], $checkout->get_value( 'apreas_escola' ) );

        woocommerce_form_field( 'apreas_turma', [
            'type'     => 'text',
            'label'    => __( 'Turma', 'apreas' ),
            'required' => true,
            'class'    => [ 'form-row-last' ],
        ], $checkout->get_value( 'apreas_turma' ) );

        // Adiciona um espaçador/quebra de linha para limpar o float do first/last
        echo '<div style="clear:both;"></div>';

        // ─────────────────────────────────────────────
        // Design Customizado - Cupom de Desconto
        // ─────────────────────────────────────────────
        if ( get_option( 'apreas_custom_coupon_enabled', 1 ) ) {
            echo '<div class="apreas-cupom-desconto" style="margin-top:2rem; padding:1.5rem; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:8px;">';
            echo '<h4 style="margin-top:0; margin-bottom:1rem; font-size:16px; color:#334155; display:flex; align-items:center; gap:8px;">';
            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 12H16c-.7 2-2 3-4 3s-3.3-1-4-3H2.5"/><path d="M5.5 5.1L2 12v6c0 1.1.9 2 2 2h16a2 2 0 002-2v-6l-3.5-6.9A2 2 0 0017 4.5h-10a2 2 0 00-1.5.6z"/></svg>';
            echo esc_html__( 'Tem um cupom de desconto?', 'apreas' ) . '</h4>';

            // Label + linha com input e botão
            echo '<label for="apreas_codigo_desconto" style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:#374151;">' . esc_html__( 'Código do Cupom', 'apreas' ) . ' <span style="font-weight:400; color:#9ca3af;">(opcional)</span></label>';
            echo '<div style="display:flex; gap:8px; align-items:center;">';
            echo '<input type="text" id="apreas_codigo_desconto" name="apreas_codigo_desconto" placeholder="' . esc_attr__( 'Digite seu código aqui', 'apreas' ) . '" value="' . esc_attr( $checkout->get_value( 'apreas_codigo_desconto' ) ) . '" style="flex:1; height:44px; padding:0 12px; border:1px solid #d1d5db; border-radius:4px; font-size:14px; background:#fff; box-sizing:border-box;" />';
            echo '<input type="hidden" id="apreas_remover_cupom" name="apreas_remover_cupom" value="0" />';
            echo '<button type="button" id="btn_apreas_aplicar_cupom" class="button alt" style="flex-shrink:0; height:44px; padding:0 20px; font-size:14px; border-radius:4px; cursor:pointer;">Aplicar</button>';
            echo '<button type="button" id="btn_apreas_remover_cupom" class="button" style="flex-shrink:0; height:44px; padding:0 16px; font-size:14px; border-radius:4px; cursor:pointer; display:none; background:#fee2e2; color:#dc2626; border-color:#fca5a5;">Remover</button>';
            echo '</div>'; // fecha flex row (input + botões)

            // Div de feedback inline — atualizado via fragment AJAX
            echo '<div id="apreas-cupom-feedback" style="margin-top:10px;"></div>';

            echo '<p style="font-size:12px; color:#64748b; margin-top:8px; margin-bottom:0;">Insira seu código e clique em <strong>Aplicar</strong> para ganhar descontos na sua compra.</p>';
            echo '</div>'; // fecha apreas-cupom-desconto


            // JavaScript: Aplicar e Remover cupom
            echo "<script>
            jQuery(document).ready(function($){

                // Mostrar/ocultar botão Remover com base no valor do campo
                function apreakToggleRemove() {
                    var val = $('#apreas_codigo_desconto').val().trim();
                    if ( val.length > 0 ) {
                        $('#btn_apreas_remover_cupom').show();
                    } else {
                        $('#btn_apreas_remover_cupom').hide();
                    }
                }
                apreakToggleRemove();
                $('#apreas_codigo_desconto').on('input', apreakToggleRemove);

                // Aplicar
                $('#btn_apreas_aplicar_cupom').on('click', function(e){
                    e.preventDefault();
                    $('#apreas_remover_cupom').val('0');
                    $('body').trigger('update_checkout');
                });

                // Remover
                $('#btn_apreas_remover_cupom').on('click', function(e){
                    e.preventDefault();
                    $('#apreas_remover_cupom').val('1');
                    $('#apreas_codigo_desconto').val('');
                    $(this).hide();
                    $('body').trigger('update_checkout');
                });

                // Enter no campo = Aplicar
                $('#apreas_codigo_desconto').on('keypress', function(e){
                    if(e.which === 13) {
                        e.preventDefault();
                        $('#apreas_remover_cupom').val('0');
                        $('body').trigger('update_checkout');
                    }
                });
            });
            </script>";
        }

        echo '</div>';
    }

    // ─────────────────────────────────────────────
    // VALIDAÇÃO
    // ─────────────────────────────────────────────
    public function validar_campos_checkout() {
        if ( ! $this->tem_produto_recordacao_escolar() ) {
            return;
        }

        $campos = [
            'apreas_aluno'  => 'Nome Completo do(a) Aluno(a)',
            'apreas_escola' => 'Escola',
            'apreas_turma'  => 'Turma',
        ];
        foreach ( $campos as $key => $label ) {
            if ( empty( $_POST[ $key ] ) ) {
                wc_add_notice( sprintf( __( 'O campo <strong>%s</strong> é obrigatório.', 'apreas' ), $label ), 'error' );
            }
        }
    }

    // ─────────────────────────────────────────────
    // SALVAR
    // ─────────────────────────────────────────────
    public function salvar_campos_checkout( $order_id ) {
        if ( ! $this->tem_produto_recordacao_escolar() ) {
            return;
        }

        $campos = [
            'apreas_aluno'         => '_apreas_aluno',
            'apreas_escola'        => '_apreas_escola',
            'apreas_turma'         => '_apreas_turma',
            'billing_neighborhood' => '_billing_neighborhood',
        ];
        foreach ( $campos as $post_key => $meta_key ) {
            if ( ! empty( $_POST[ $post_key ] ) ) {
                update_post_meta( $order_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
            }
        }
    }

    // ─────────────────────────────────────────────
    // ADMIN — card no pedido
    // ─────────────────────────────────────────────
    public function exibir_campos_admin( $order ) {
        $aluno  = get_post_meta( $order->get_id(), '_apreas_aluno',  true );
        $escola = get_post_meta( $order->get_id(), '_apreas_escola', true );
        $turma  = get_post_meta( $order->get_id(), '_apreas_turma',  true );
        $bairro = get_post_meta( $order->get_id(), '_billing_neighborhood', true );

        if ( ! $aluno && ! $escola && ! $turma && ! $bairro ) return;

        // SVGs — mesma cor neutra para todos
        $svg_pessoa = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#6b7280"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V21h19.2v-1.8c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';
        $svg_escola = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#6b7280"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 12.27L4.56 11 12 6.73 19.44 11 12 15.27zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>';
        $svg_turma  = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#6b7280"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>';
        $svg_bairro = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#6b7280" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>';
        $svg_header = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="rgba(255,255,255,0.85)"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V21h19.2v-1.8c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';

        // Estilos reutilizáveis
        $lbl = 'display:block;font-size:12px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:2px;';
        $val = 'display:block;font-size:14px;font-weight:600;color:#111827;';
        $ico = 'background:#f3f4f6;border-radius:4px;width:25px;height:25px;display:flex;align-items:center;justify-content:center;flex-shrink:0;';

        // Helper: bloco de campo com ícone
        $row = function( $svg, $label, $value ) use ( $lbl, $val, $ico ) {
            return '
            <div style="display:flex;align-items:flex-start;gap:8px;background:#f9fafb;border-radius:6px;padding:9px 11px;">
                <div style="' . $ico . '">' . $svg . '</div>
                <div>
                    <span style="' . $lbl . '">' . esc_html( $label ) . '</span>
                    <span style="' . $val . '">' . esc_html( $value ) . '</span>
                </div>
            </div>';
        };
        ?>
        <div style="margin-top:18px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden;font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">

            <!-- Cabeçalho escuro neutro -->
            <div style="background:#1f2937;padding:10px 13px;display:flex;align-items:center;gap:8px;">
                <div style="background:rgba(255,255,255,0.1);border-radius:4px;width:22px;height:22px;display:flex;align-items:center;justify-content:center;">
                    <?php echo $svg_header; ?>
                </div>
                <span style="color:#f3f4f6;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Dados do Aluno</span>
            </div>

            <!-- Corpo -->
            <div style="padding:12px 14px 14px;display:flex;flex-direction:column;gap:8px;">

                <?php if ( $aluno ) : ?>
                <div style="display:flex;align-items:flex-start;gap:8px;border-bottom:1px solid #f3f4f6;padding-bottom:10px;margin-bottom:2px;">
                    <div style="<?php echo $ico; ?>"><?php echo $svg_pessoa; ?></div>
                    <div>
                        <span style="<?php echo $lbl; ?>">Nome do Aluno</span>
                        <span style="<?php echo $val; ?>"><?php echo esc_html( $aluno ); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <?php
                    if ( $escola ) echo $row( $svg_escola, 'Escola', $escola );
                    if ( $turma  ) echo $row( $svg_turma,  'Turma',  $turma  );
                    ?>
                </div>

            </div>
        </div>
        <?php
    }

    // ─────────────────────────────────────────────
    // E-MAIL
    // ─────────────────────────────────────────────
    public function campos_no_email( $fields, $sent_to_admin, $order ) {
        $id = $order->get_id();
        $mapa = [
            'aluno'  => [ 'label' => 'Nome do Aluno', 'value' => get_post_meta( $id, '_apreas_aluno',  true ) ],
            'escola' => [ 'label' => 'Escola',         'value' => get_post_meta( $id, '_apreas_escola', true ) ],
            'turma'  => [ 'label' => 'Turma',          'value' => get_post_meta( $id, '_apreas_turma',  true ) ],
        ];
        foreach ( $mapa as $key => $data ) {
            if ( ! empty( $data['value'] ) ) $fields[ $key ] = $data;
        }

        // Adiciona Bairro separadamente se existir
        $bairro = get_post_meta( $id, '_billing_neighborhood', true );
        if ( $bairro ) {
            $fields['bairro'] = [ 'label' => 'Bairro', 'value' => $bairro ];
        }

        return $fields;
    }

    /**
     * Adiciona o campo Bairro nos campos de faturamento do admin para ser editável.
     */
    public function adicionar_bairro_admin_billing( $fields ) {
        $fields['neighborhood'] = [
            'label' => __( 'Bairro', 'apreas' ),
            'show'  => false, // oculta a linha separada, pois vamos injetar no endereço formatado
        ];
        return $fields;
    }

    /**
     * Formata o endereço para o Brasil incluindo o Bairro.
     */
    public function formatar_endereco_br( $formats ) {
        $formats['BR'] = "{address_1}, {address_2}\n{neighborhood}\n{city} - {state}\n{postcode}\n{country}";
        return $formats;
    }

    /**
     * Injeta o valor do bairro no array de endereço para que o placeholder funcione.
     */
    public function adicionar_bairro_endereco_objeto( $address, $order ) {
        $bairro = $order->get_meta( '_billing_neighborhood' );
        if ( $bairro ) {
            $address['neighborhood'] = $bairro;
        }
        return $address;
    }

    /**
     * Substitui o placeholder {neighborhood} pelo valor real.
     */
    public function substituir_bairro_endereco( $replacements, $args ) {
        $replacements['{neighborhood}'] = ! empty( $args['neighborhood'] ) ? $args['neighborhood'] : '';
        return $replacements;
    }

    /**
     * Força a inserção do bairro na string final do endereço (útil no Admin).
     */
    public function adicionar_bairro_string_admin( $address, $raw_address, $order ) {
        // Se já contiver o bairro ou não for admin, retorna (evita duplicação)
        $bairro = $order->get_meta( '_billing_neighborhood' );
        if ( ! $bairro ) return $address;

        // Se o bairro já está na string (limpa tags para comparar)
        if ( strpos( strip_tags( $address ), $bairro ) !== false ) {
            return $address;
        }

        // Tenta inserir logo após a Rua (address_1)
        $rua = $order->get_billing_address_1();
        if ( $rua && strpos( $address, $rua ) !== false ) {
            return str_replace( $rua, $rua . '<br>' . $bairro, $address );
        }

        // Se não achou a rua, apenas concatena no início ou fim
        return $address . '<br>' . $bairro;
    }
}

Checkout::getInstance();
