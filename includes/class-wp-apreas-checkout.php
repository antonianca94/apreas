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
    }

    // ─────────────────────────────────────────────
    // CHECKOUT — renderiza campos no formulário
    // ─────────────────────────────────────────────
    public function render_campos_checkout( $checkout ) {
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

        woocommerce_form_field( 'apreas_serie', [
            'type'     => 'text',
            'label'    => __( 'Série', 'apreas' ),
            'required' => true,
            'class'    => [ 'form-row-last' ],
        ], $checkout->get_value( 'apreas_serie' ) );

        woocommerce_form_field( 'apreas_turma', [
            'type'     => 'text',
            'label'    => __( 'Turma', 'apreas' ),
            'required' => true,
            'class'    => [ 'form-row-wide' ],
        ], $checkout->get_value( 'apreas_turma' ) );

        echo '</div>';
    }

    // ─────────────────────────────────────────────
    // VALIDAÇÃO
    // ─────────────────────────────────────────────
    public function validar_campos_checkout() {
        $campos = [
            'apreas_aluno'  => 'Nome Completo do(a) Aluno(a)',
            'apreas_escola' => 'Escola',
            'apreas_serie'  => 'Série',
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
        $campos = [
            'apreas_aluno'  => '_apreas_aluno',
            'apreas_escola' => '_apreas_escola',
            'apreas_serie'  => '_apreas_serie',
            'apreas_turma'  => '_apreas_turma',
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
        $serie  = get_post_meta( $order->get_id(), '_apreas_serie',  true );
        $turma  = get_post_meta( $order->get_id(), '_apreas_turma',  true );

        if ( ! $aluno && ! $escola && ! $serie && ! $turma ) return;

        // SVGs — mesma cor neutra para todos
        $svg_pessoa = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#6b7280"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V21h19.2v-1.8c0-3.2-6.4-4.8-9.6-4.8z"/></svg>';
        $svg_escola = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#6b7280"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 12.27L4.56 11 12 6.73 19.44 11 12 15.27zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>';
        $svg_serie  = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#6b7280"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.5 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zM21 18.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>';
        $svg_turma  = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#6b7280"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>';
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
                    if ( $serie  ) echo $row( $svg_serie,  'Série',  $serie  );
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
            'serie'  => [ 'label' => 'Série',          'value' => get_post_meta( $id, '_apreas_serie',  true ) ],
            'turma'  => [ 'label' => 'Turma',          'value' => get_post_meta( $id, '_apreas_turma',  true ) ],
        ];
        foreach ( $mapa as $key => $data ) {
            if ( ! empty( $data['value'] ) ) $fields[ $key ] = $data;
        }
        return $fields;
    }
}

Checkout::getInstance();
