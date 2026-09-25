<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Relatório Avançado de Pedidos (WooCommerce).
 *
 * Uma linha por pedido, trazendo dados do aluno, do responsável (pai),
 * endereço completo, itens do pedido, data e checkboxes Feito/Entregue
 * editáveis inline via AJAX. Filtros por Nome do Aluno e Escola, com
 * exportação em CSV.
 */
class Relatorios {

    private static $instance;

    const CAPABILIDADE = 'manage_woocommerce';
    const SLUG         = 'apreas-relatorio';
    const NONCE        = 'apreas_toggle_pedido_nonce';

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'adicionar_pagina' ], 9 );
        add_action( 'admin_init', [ $this, 'processar_export_csv' ] );
        add_action( 'wp_ajax_apreas_toggle_pedido', [ $this, 'ajax_toggle_pedido' ] );
        add_action( 'wp_ajax_apreas_filtrar_pedidos', [ $this, 'ajax_filtrar_pedidos' ] );
        add_action( 'admin_head', [ $this, 'ocultar_notices' ] );
        add_action( 'admin_print_styles-toplevel_page_' . self::SLUG, [ $this, 'carregar_fonte' ] );
    }

    // ─────────────────────────────────────────────
    // FONTE — Google Sans Flex (somente nesta tela)
    // ─────────────────────────────────────────────
    public function carregar_fonte() {
        wp_enqueue_style(
            'apreas-google-sans-flex',
            'https://fonts.googleapis.com/css2?family=Google+Sans+Flex:wght@1..1000&display=swap',
            [],
            null
        );
    }

    // ─────────────────────────────────────────────
    // OCULTA NOTICES DE OUTROS PLUGINS NA PÁGINA
    // ─────────────────────────────────────────────
    public function ocultar_notices() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }
        $tela = get_current_screen();
        if ( ! $tela || ( $tela->id !== 'woocommerce_page_' . self::SLUG && $tela->id !== 'toplevel_page_' . self::SLUG ) ) {
            return;
        }
        echo '<style id="apreas-oculta-notices">
            #rsssl-message,
            #rsssl-message.active,
            .really-simple-plugins.notice {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
            }
        </style>';
    }

    // ─────────────────────────────────────────────
    // MENU RAIZ "Apreas" — Relatório (primeiro submenu)
    // ─────────────────────────────────────────────
    public function adicionar_pagina() {
        add_menu_page(
            'Apreas',
            'Apreas',
            'manage_woocommerce',
            self::SLUG,
            [ $this, 'render_pagina' ],
            'dashicons-awards',
            30
        );

        add_submenu_page(
            self::SLUG,
            'Relatório',
            'Relatório',
            self::CAPABILIDADE,
            self::SLUG,
            [ $this, 'render_pagina' ]
        );
    }

    // ─────────────────────────────────────────────
    // FILTROS (GET)
    // ─────────────────────────────────────────────
    private function obter_parametro( $nome ) {
        if ( isset( $_POST[ $nome ] ) ) {
            return sanitize_text_field( wp_unslash( $_POST[ $nome ] ) );
        }
        if ( isset( $_GET[ $nome ] ) ) {
            return sanitize_text_field( wp_unslash( $_GET[ $nome ] ) );
        }
        return '';
    }

    private function get_filtro_nome_aluno() {
        return $this->obter_parametro( 'filtro_nome_aluno' );
    }

    private function get_filtro_escola() {
        return $this->obter_parametro( 'filtro_escola' );
    }

    private function get_filtro_nome_pai() {
        return $this->obter_parametro( 'filtro_nome_pai' );
    }

    private function get_filtro_data_inicio() {
        return $this->obter_parametro( 'filtro_data_inicio' );
    }

    private function get_filtro_data_fim() {
        return $this->obter_parametro( 'filtro_data_fim' );
    }

    private function get_filtro_ano() {
        return $this->obter_parametro( 'filtro_ano' );
    }

    private function get_filtro_categoria() {
        return $this->obter_parametro( 'filtro_categoria' );
    }

    /**
     * Quantidade de registros por página: 10, 25, 50 ou 100 (padrão 25).
     */
    private function get_por_pagina() {
        $valor = absint( $this->obter_parametro( 'por_pagina' ) );
        return in_array( $valor, [ 10, 25, 50, 100 ], true ) ? $valor : 25;
    }

    /**
     * Categorias de produto (product_cat) disponíveis como filtro.
     */
    private function listar_categorias() {
        $cats = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ] );
        return ( is_array( $cats ) && ! is_wp_error( $cats ) ) ? $cats : [];
    }

    /**
     * Lista as escolas distintas realmente gravadas nos pedidos
     * (_apreas_escola é texto livre no checkout).
     */
    private function listar_escolas_pedidos() {
        global $wpdb;
        $resultado = $wpdb->get_col(
            "SELECT DISTINCT meta_value
             FROM {$wpdb->postmeta}
             WHERE meta_key = '_apreas_escola'
               AND meta_value != ''
             ORDER BY meta_value ASC"
        );
        return is_array( $resultado ) ? $resultado : [];
    }

    // ─────────────────────────────────────────────
    // PEDIDOS FILTRADOS (IDs)
    // ─────────────────────────────────────────────
    /**
     * IDs dos pedidos aplicando os filtros ativos.
     * Lista qualquer pedido do WooCommerce (não exige dados de aluno),
     * com filtros combináveis: aluno, escola, nome do pai, range de datas,
     * ano e categoria de produto. Filtragem direta via SQL.
     */
    private function ids_pedidos_filtrados() {
        global $wpdb;

        $join   = '';
        $where  = [];

        // Nome do aluno
        $nome = $this->get_filtro_nome_aluno();
        if ( $nome !== '' ) {
            $where[] = $wpdb->prepare(
                "p.ID IN (
                    SELECT post_id FROM {$wpdb->postmeta}
                    WHERE meta_key = '_apreas_aluno' AND meta_value LIKE %s )",
                '%' . $wpdb->esc_like( $nome ) . '%'
            );
        }

        // Escola
        $escola = $this->get_filtro_escola();
        if ( $escola !== '' ) {
            $where[] = $wpdb->prepare(
                "p.ID IN (
                    SELECT post_id FROM {$wpdb->postmeta}
                    WHERE meta_key = '_apreas_escola' AND meta_value LIKE %s )",
                '%' . $wpdb->esc_like( $escola ) . '%'
            );
        }

        // Nome do pai (nome de faturamento)
        $pai = $this->get_filtro_nome_pai();
        if ( $pai !== '' ) {
            $where[] = $wpdb->prepare(
                "p.ID IN (
                    SELECT post_id FROM {$wpdb->postmeta}
                    WHERE meta_key IN ( '_billing_first_name', '_billing_last_name' )
                      AND meta_value LIKE %s )",
                '%' . $wpdb->esc_like( $pai ) . '%'
            );
        }

        // Range de datas do pedido (post_date)
        $data_inicio = $this->get_filtro_data_inicio();
        if ( $data_inicio !== '' && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data_inicio ) ) {
            $where[] = $wpdb->prepare( 'p.post_date >= %s', $data_inicio . ' 00:00:00' );
        }
        $data_fim = $this->get_filtro_data_fim();
        if ( $data_fim !== '' && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data_fim ) ) {
            $where[] = $wpdb->prepare( 'p.post_date <= %s', $data_fim . ' 23:59:59' );
        }

        // Ano (ex.: 2027) — combinável com os demais filtros
        $ano = $this->get_filtro_ano();
        if ( $ano !== '' && preg_match( '/^\d{4}$/', $ano ) ) {
            $where[] = $wpdb->prepare( 'YEAR( p.post_date ) = %d', (int) $ano );
        }

        // Categoria de produto (pedido contém produto da categoria)
        $categoria = $this->get_filtro_categoria();
        if ( $categoria !== '' ) {
            $join .= " INNER JOIN {$wpdb->prefix}woocommerce_order_items oi
                          ON oi.order_id = p.ID AND oi.order_item_type = 'line_item'";
            $join .= " INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
                          ON oim.order_item_id = oi.order_item_id AND oim.meta_key = '_product_id'";
            $join .= " INNER JOIN {$wpdb->prefix}term_relationships tr
                          ON tr.object_id = oim.meta_value";
            $join .= " INNER JOIN {$wpdb->prefix}term_taxonomy tt
                          ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'";
            $join .= $wpdb->prepare(
                " INNER JOIN {$wpdb->prefix}terms t
                      ON t.term_id = tt.term_id AND t.slug = %s",
                sanitize_title( $categoria )
            );
        }

        $sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p" . $join;
        $sql .= " WHERE p.post_type = 'shop_order'";
        if ( $where ) {
            $sql .= ' AND ' . implode( ' AND ', $where );
        }

        $ids = $wpdb->get_col( $sql );
        return array_map( 'intval', is_array( $ids ) ? $ids : [] );
    }

    private function consultar_pedidos( $posts_por_pagina, $pagina = 1 ) {
        $ids = $this->ids_pedidos_filtrados();

        $args = [
            'post_type'      => 'shop_order',
            'post_status'    => 'any',
            'post__in'       => $ids ? $ids : [ 0 ],
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => $posts_por_pagina,
        ];

        if ( $posts_por_pagina > 0 ) {
            $args['paged'] = max( 1, absint( $pagina ) );
        }

        return new \WP_Query( $args );
    }

    // ─────────────────────────────────────────────
    // DADOS DE UMA COLUNA
    // ─────────────────────────────────────────────
    private function nome_responsavel( $order ) {
        $primeiro = trim( (string) $order->get_billing_first_name() );
        $ultimo   = trim( (string) $order->get_billing_last_name() );
        return trim( $primeiro . ' ' . $ultimo );
    }

    private function endereco_completo( $order ) {
        $partes = [];

        $rua = trim( (string) $order->get_billing_address_1() );
        if ( $rua !== '' ) {
            $partes[] = $rua;
        }

        $complemento = trim( (string) $order->get_billing_address_2() );
        if ( $complemento !== '' ) {
            $partes[] = $complemento;
        }

        $bairro = trim( (string) $order->get_meta( '_billing_neighborhood' ) );
        if ( $bairro !== '' ) {
            $partes[] = $bairro;
        }

        $cidade = trim( (string) $order->get_billing_city() );
        $estado = trim( (string) $order->get_billing_state() );
        if ( $cidade !== '' || $estado !== '' ) {
            $partes[] = trim( $cidade . ' - ' . $estado, ' -' );
        }

        $cep = trim( (string) $order->get_billing_postcode() );
        if ( $cep !== '' ) {
            $partes[] = $cep;
        }

        return implode( ', ', $partes );
    }

    /**
     * Versão texto (CSV): "Nome (x2), Outro (x3)".
     */
    private function lista_pedidos( $order ) {
        $itens = [];
        foreach ( $order->get_items() as $item ) {
            $nome = $item->get_name();
            $qtd  = absint( $item->get_quantity() );
            $itens[] = ( $qtd > 1 ) ? $nome . ' (x' . $qtd . ')' : $nome;
        }
        return implode( ', ', $itens );
    }

    /**
     * Versão HTML (tabela): cada item do pedido em linha própria com badge de quantidade.
     */
    private function lista_pedidos_html( $order ) {
        $itens = [];
        foreach ( $order->get_items() as $item ) {
            $nome = esc_html( $item->get_name() );
            $qtd  = absint( $item->get_quantity() );
            if ( $qtd > 1 ) {
                $itens[] = '<span class="ap-item-linha">' . $nome . ' <span class="ap-qtd-badge">x' . $qtd . '</span></span>';
            } else {
                $itens[] = '<span class="ap-item-linha">' . $nome . '</span>';
            }
        }
        return implode( '', $itens );
    }

    /**
     * Valor total do pedido formatado em reais.
     */
    private function total_pedido( $order ) {
        $total = (float) $order->get_total();
        return wc_price( $total, [ 'currency' => $order->get_currency() ? $order->get_currency() : get_woocommerce_currency() ] );
    }

    private function data_pedido( $order ) {
        $data = $order->get_date_created();
        if ( ! $data ) {
            return '—';
        }
        $dt = $data->setTimezone( new \DateTimeZone( wp_timezone_string() ) );
        return $dt->format( 'd/m/Y H:i' );
    }

    /**
     * Paginação simples (mantém os filtros ativos e o por_pagina).
     */
    private function paginacao( $total_paginas, $pagina_atual, $filtros ) {
        $total_paginas = (int) $total_paginas;
        if ( $total_paginas <= 1 ) {
            return '';
        }

        $base = admin_url( 'admin.php?page=' . self::SLUG );

        $url_pagina = function ( $pagina ) use ( $base, $filtros ) {
            $args = array_merge( $filtros, [ 'paged' => $pagina ] );
            return add_query_arg( $args, $base );
        };

        $prev = ( $pagina_atual > 1 ) ? $url_pagina( $pagina_atual - 1 ) : '';
        $next = ( $pagina_atual < $total_paginas ) ? $url_pagina( $pagina_atual + 1 ) : '';

        $html = '<div class="apreas-paginacao">';
        $html .= '<span class="displaying-num">Página <strong>' . (int) $pagina_atual . '</strong> de <strong>' . $total_paginas . '</strong></span>';

        $pag_ativa = function ( $pagina, $url, $rotulo, $extra = '' ) {
            return '<a class="ap-pag-link ' . $extra . '" href="' . esc_url( $url ) . '" data-pagina="' . (int) $pagina . '">' . $rotulo . '</a>';
        };
        $pag_inalva = function ( $rotulo ) {
            return '<span class="ap-pag-link disabled" aria-hidden="true">' . $rotulo . '</span>';
        };

        if ( $prev ) {
            $html .= $pag_ativa( $pagina_atual - 1, $prev, '&laquo;' );
        } else {
            $html .= $pag_inalva( '&laquo;' );
        }

        for ( $p = 1; $p <= $total_paginas; $p++ ) {
            if ( $p === (int) $pagina_atual ) {
                $html .= '<a class="ap-pag-link current" aria-current="page" data-pagina="' . $p . '" href="' . esc_url( $url_pagina( $p ) ) . '">' . $p . '</a>';
            } else {
                $html .= $pag_ativa( $p, $url_pagina( $p ), $p );
            }
        }

        if ( $next ) {
            $html .= $pag_ativa( $pagina_atual + 1, $next, '&raquo;' );
        } else {
            $html .= $pag_inalva( '&raquo;' );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Separa o valor único "Série e Turma" (ex.: "3º Ano A") em duas colunas.
     */
    private function quebrar_serie_turma( $valor ) {
        $valor = trim( (string) $valor );
        if ( $valor === '' ) {
            return [ '', '' ];
        }

        // 0) Turma / período / número solto: "turma 1", "sala 2", "tarde", "manhã", "noite", "1"
        if ( preg_match( '/^(turma|sala)\b/iu', $valor )
            || preg_match( '/^(manh[ãa]|tarde|noite|integral)$/iu', $valor )
            || preg_match( '/^\d{1,2}$/iu', $valor ) ) {
            return [ '', $valor ];
        }

        // 1) "3º Ano A", "3ºANO B", "1ª Série C", "4º Período D", "Pré II A", "2º Ano manhã"
        if ( preg_match( '/^(.+?(?:Ano|S[eé]rie|Per[ií]odo|Fase)[\.:]?)\s*(.*)$/iu', $valor, $m ) ) {
            $serie = trim( $m[1] );
            $turma = trim( $m[2], " \t\n\r\0\x0B-–" );
            if ( $turma !== '' ) {
                return [ $serie, $turma ];
            }
        }

        // 2) Dígito + letra(s) soltas no final, ex.: "6 A"
        if ( preg_match( '/^(.+\d[º°ª]?)\s+([A-Za-zÀ-ÖØ-öø-ÿ]+)$/iu', $valor, $m ) ) {
            $s = trim( $m[1] );
            $t = trim( $m[2] );
            if ( preg_match( '/(Ano|S[eé]rie|Per[ií]odo|Fase)/iu', $s ) ) {
                return [ $s, $t ];
            }
        }

        // 3) Fallback: valor inteiro em Série
        return [ $valor, '' ];
    }

    // ─────────────────────────────────────────────
    // ARGUMENTOS DOS FILTROS ATIVOS (para links/paginação/CSV)
    // ─────────────────────────────────────────────
    private function argumentos_filtro() {
        $args = [];
        foreach ( [ 'filtro_nome_aluno', 'filtro_escola', 'filtro_nome_pai', 'filtro_data_inicio', 'filtro_data_fim', 'filtro_ano', 'filtro_categoria' ] as $campo ) {
            $valor = $this->obter_parametro( $campo );
            if ( $valor !== '' ) {
                $args[ $campo ] = $valor;
            }
        }
        $por_pagina = $this->get_por_pagina();
        if ( $por_pagina !== 25 ) {
            $args['por_pagina'] = $por_pagina;
        }
        return $args;
    }

    // ─────────────────────────────────────────────
    // MÉTRICAS — totais do conjunto filtrado
    // ─────────────────────────────────────────────
    private function estatisticas() {
        global $wpdb;

        $ids = $this->ids_pedidos_filtrados();

        $stats = [
            'total'     => count( $ids ),
            'feito'     => 0,
            'entregue'  => 0,
            'pendente'  => 0,
        ];

        if ( $ids ) {
            $in = implode( ',', $ids );
            $linhas = $wpdb->get_results(
                "SELECT meta_key, meta_value
                 FROM {$wpdb->postmeta}
                 WHERE post_id IN ({$in})
                   AND meta_key IN ('_apreas_pedido_feito', '_apreas_pedido_entregue')"
            );
            $linhas = $linhas ?: [];
            foreach ( $linhas as $linha ) {
                if ( $linha->meta_value === '1' ) {
                    if ( $linha->meta_key === '_apreas_pedido_feito' ) {
                        ++$stats['feito'];
                    } else {
                        ++$stats['entregue'];
                    }
                }
            }
        }

        $stats['pendente'] = max( 0, $stats['total'] - $stats['entregue'] );

        return $stats;
    }

    // ─────────────────────────────────────────────
    // CSS + JS (inline, página exclusiva)
    // ─────────────────────────────────────────────
    private function estilos_script() {
        ?>
        <style>
            .apreas-relatorio-wrap {
                --ap-primary: #D90D28;
                --ap-primary-dark: #b00b20;
                --ap-bg: #f0f1f5;
                --ap-card: #ffffff;
                --ap-border: #e5e7ee;
                --ap-text: #1f2937;
                --ap-muted: #6b7280;
                --ap-green: #16a34a;
                --ap-blue: #2563eb;
                --ap-indigo: #6366f1;
                --ap-orange: #f59e0b;
                --ap-radius: 14px;
                --ap-shadow: 0 1px 2px rgba(16,24,40,.04), 0 8px 24px -12px rgba(16,24,40,.14);
                margin: 16px 20px 0 0;
                font-family: 'Google Sans Flex', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                color: var(--ap-text);
            }

            /* ── STATUS ── */
            .apreas-status {
                display: none;
                font-size: 12px;
                font-weight: 600;
                padding: 9px 14px;
                border-radius: 10px;
                margin: 0 0 16px;
                background: #eff6ff;
                color: #1d4ed8;
                border: 1px solid #bfdbfe;
            }
            .apreas-status.erro { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

            /* ── HEADER ── */
            .apreas-relatorio-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                flex-wrap: wrap;
                background: linear-gradient(135deg, #ffffff 0%, #fbfbfe 100%);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius);
                padding: 18px 22px;
                box-shadow: var(--ap-shadow);
                margin-bottom: 20px;
            }
            .apreas-header-left { display: flex; align-items: center; gap: 14px; }
            .apreas-header-icon {
                width: 46px; height: 46px; border-radius: 12px;
                background: linear-gradient(135deg, var(--ap-primary) 0%, var(--ap-primary-dark) 100%);
                color: #fff;
                font-size: 22px; line-height: 46px; text-align: center;
                box-shadow: 0 6px 14px -6px rgba(217,13,40,.55);
            }
            .apreas-relatorio-header h1 { margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -.2px; }
            .apreas-relatorio-header p { margin: 3px 0 0; font-size: 13px; color: var(--ap-muted); }

            .apreas-btn {
                display: inline-flex; align-items: center; gap: 8px;
                border-radius: 10px; padding: 10px 18px; font-size: 13px; font-weight: 600;
                text-decoration: none; cursor: pointer; border: 1px solid transparent;
                transition: all .15s ease;
            }
            .apreas-btn:focus { box-shadow: 0 0 0 3px rgba(217,13,40,.25); outline: none; }
            .apreas-btn-csv { background: #fff; border-color: var(--ap-border); color: var(--ap-text); }
            .apreas-btn-csv:hover { background: #f7f8fb; border-color: #cdd3e0; color: var(--ap-primary); }
            .apreas-btn-csv .dashicons { font-size: 16px; width: 16px; height: 16px; color: var(--ap-green); }
            .apreas-btn-primary { background: var(--ap-primary); color: #fff; }
            .apreas-btn-primary:hover { background: var(--ap-primary-dark); color: #fff; }
            .apreas-btn-clear { background: #fff; border-color: var(--ap-border); color: var(--ap-muted); }
            .apreas-btn-clear:hover { color: var(--ap-text); border-color: #cdd3e0; background: #f7f8fb; }

            /* ── MÉTRICAS ── */
            .apreas-metrics {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 16px;
                margin-bottom: 20px;
            }
            .apreas-metric {
                display: flex; align-items: center; gap: 14px;
                background: var(--ap-card);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius);
                padding: 16px 18px;
                box-shadow: var(--ap-shadow);
            }
            .apreas-metric-icon {
                width: 44px; height: 44px; border-radius: 12px;
                display: flex; align-items: center; justify-content: center;
                color: #fff; flex-shrink: 0;
                transition: transform .15s ease, box-shadow .15s ease;
            }
            .apreas-metric-icon svg { width: 24px; height: 24px; }
            .apreas-metric:hover .apreas-metric-icon { transform: scale(1.08); }
            .apreas-metric-icon.m-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); box-shadow: 0 6px 14px -6px rgba(59,130,246,.55); }
            .apreas-metric-icon.m-green { background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 6px 14px -6px rgba(34,197,94,.55); }
            .apreas-metric-icon.m-indigo { background: linear-gradient(135deg, #818cf8, #6366f1); box-shadow: 0 6px 14px -6px rgba(99,102,241,.55); }
            .apreas-metric-icon.m-orange { background: linear-gradient(135deg, #fbbf24, #f59e0b); box-shadow: 0 6px 14px -6px rgba(245,158,11,.55); }
            .apreas-metric-value { display: block; font-size: 22px; font-weight: 800; letter-spacing: -.5px; line-height: 1; }
            .apreas-metric-label { display: block; font-size: 12px; color: var(--ap-muted); font-weight: 500; margin-top: 4px; }

            /* ── FILTROS ── */
            .apreas-filtros {
                display: flex; flex-wrap: wrap; gap: 14px 18px; align-items: flex-end;
                background: var(--ap-card);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius);
                padding: 18px 20px;
                margin-bottom: 20px;
                box-shadow: var(--ap-shadow);
            }
            .apreas-filtro-campo label { display: block; font-weight: 600; font-size: 12px; margin-bottom: 6px; color: #374151; }
            .apreas-filtro-campo .ctl {
                height: 40px; min-width: 240px; padding: 0 12px;
                border: 1px solid #d8dde8; border-radius: 10px;
                font-size: 13px; color: var(--ap-text); background: #fff;
                transition: border-color .15s ease, box-shadow .15s ease;
                box-sizing: border-box;
            }
            .apreas-filtro-campo .ctl:focus {
                outline: none; border-color: var(--ap-primary);
                box-shadow: 0 0 0 3px rgba(217,13,40,.12);
            }
            .apreas-filtro-campo select.ctl { cursor: pointer; }
            .apreas-filtro-acoes { display: flex; gap: 8px; }

            /* ── TABELA ── */
            .apreas-tabela-card {
                background: var(--ap-card);
                border: 1px solid var(--ap-border);
                border-radius: var(--ap-radius);
                box-shadow: var(--ap-shadow);
                overflow: hidden;
            }
            .apreas-tabela-top, .apreas-tabela-bottom {
                display: flex; align-items: center; justify-content: space-between;
                gap: 12px; flex-wrap: wrap; padding: 12px 18px;
            }
            .apreas-tabela-top { border-bottom: 1px solid var(--ap-border); }
            .apreas-tabela-bottom { border-top: 1px solid var(--ap-border); }
            .apreas-resultado { font-size: 12.5px; color: var(--ap-muted); font-weight: 500; }
            .apreas-resultado strong { color: var(--ap-text); }
            .apreas-tabela-scroll { overflow-x: auto; }

            table.apreas-tabela { width: 100%; border-collapse: collapse; font-size: 13px; }
            table.apreas-tabela thead th {
                background: #f8fafc; color: #475569;
                font-size: 11px; text-transform: uppercase; letter-spacing: .6px;
                font-weight: 700; text-align: left;
                padding: 12px 14px; border-bottom: 1px solid var(--ap-border);
                white-space: nowrap;
            }
            table.apreas-tabela thead th.apreas-check { text-align: center; }
            table.apreas-tabela tbody td {
                padding: 12px 14px; border-bottom: 1px solid #eef0f6; vertical-align: top;
            }
            table.apreas-tabela tbody tr:nth-child(even) { background: #fafbfe; }
            table.apreas-tabela tbody tr:hover { background: #f4f6fb; }
            table.apreas-tabela tbody tr:last-child td { border-bottom: none; }

            td.apreas-check { text-align: center; white-space: nowrap; }
            input[type="checkbox"].apreas-pedido-check {
                width: 18px; height: 18px;
                accent-color: var(--ap-primary);
                cursor: pointer; margin: 0;
            }
            .apreas-negrito { font-weight: 600; }
            .apreas-aluno { font-weight: 600; color: #111827; }
            .apreas-itens { color: #374151; max-width: 300px; }
            .ap-item-linha { display: block; padding: 2px 0; line-height: 1.5; }
            .ap-item-linha + .ap-item-linha { border-top: 1px dashed #eef0f6; }
            .ap-qtd-badge {
                display: inline-block; margin-left: 4px; padding: 0 7px;
                font-size: 10.5px; font-weight: 700;
                background: #fef2f2; color: var(--ap-primary);
                border-radius: 999px;
            }
            .apreas-total { white-space: nowrap; font-weight: 700; color: #111827; }
            .apreas-data { white-space: nowrap; }

            .apreas-badge {
                display: inline-flex; align-items: center; gap: 5px;
                font-size: 10.5px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
                border-radius: 999px; padding: 3px 9px; margin-left: 6px;
            }
            .apreas-badge::before { content: ""; width: 6px; height: 6px; border-radius: 50%; }
            .apreas-badge.badge-sim { background: #ecfdf3; color: #15803d; }
            .apreas-badge.badge-sim::before { background: #22c55e; }
            .apreas-badge.badge-nao { background: #f1f1f4; color: #8a8f98; }
            .apreas-badge.badge-nao::before { background: #c3c8d1; }

            .link-whatsapp {
                display: inline-flex; align-items: center; gap: 6px;
                color: var(--ap-text); text-decoration: none; font-weight: 500;
            }
            .link-whatsapp:hover { color: #25d366; }
            .link-whatsapp .dashicons { font-size: 15px; width: 15px; height: 15px; }

            .link-pedido { text-decoration: none; color: var(--ap-text); font-weight: 600; }
            .link-pedido:hover { color: var(--ap-primary); }
            .apreas-data .apreas-num-pedido {
                display: block; font-size: 11px; font-weight: 500;
                color: var(--ap-muted); text-transform: uppercase; letter-spacing: .4px;
            }

            /* ── PAGINAÇÃO ── */
            .apreas-paginacao { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
            .apreas-paginacao .displaying-num { font-size: 12px; color: var(--ap-muted); margin-right: 8px; }
            .apreas-paginacao .displaying-num strong { color: var(--ap-text); }
            .ap-pag-link {
                display: inline-flex; align-items: center; justify-content: center;
                min-width: 32px; height: 32px; padding: 0 9px;
                border: 1px solid var(--ap-border); border-radius: 8px;
                background: #fff; color: var(--ap-muted);
                font-size: 12px; font-weight: 600; text-decoration: none;
                transition: all .12s ease;
            }
            .ap-pag-link:hover { border-color: var(--ap-primary); color: var(--ap-primary); }
            .ap-pag-link.current {
                background: var(--ap-primary); border-color: var(--ap-primary); color: #fff;
                box-shadow: 0 4px 10px -4px rgba(217,13,40,.5);
            }
            .ap-pag-link.disabled { opacity: .4; pointer-events: none; }

            /* ── VAZIO ── */
            .apreas-vazio {
                display: flex; flex-direction: column; align-items: center; gap: 6px;
                padding: 60px 20px; text-align: center; color: var(--ap-muted);
                background: var(--ap-card);
                border: 1px solid var(--ap-border); border-radius: var(--ap-radius);
                box-shadow: var(--ap-shadow); margin-bottom: 20px;
            }
            .apreas-vazio .dashicons { font-size: 42px; width: 42px; height: 42px; color: #c3c8d1; }
            .apreas-vazio strong { color: var(--ap-text); font-size: 15px; }
        </style>
        <script>
        jQuery(document).ready(function ($) {

            var $wrap   = $('#apreas-wrap');
            var baseUrl = $wrap.data('url-base');
            var timer;
            var $status = $('#apreas-status');

            // Cache/estrutura antiga: um único recarregamento com cache-buster.
            if (!$wrap.length || !$('#apreas-conteudo').length) {
                if (!sessionStorage.getItem('apreas_tentou_recarregar')) {
                    sessionStorage.setItem('apreas_tentou_recarregar', '1');
                    var rec = location.href;
                    rec += (rec.indexOf('?') !== -1 ? '&' : '?') + 'apreas_v=' + Date.now();
                    location.replace(rec);
                    return;
                }
            }

            // Navega pelo mesmo caminho GET que o carregamento inicial (sempre funciona).
            function navegarPara() {
                var args = {};
                var n  = $('#filtro_nome_aluno').val();
                var p  = $('#filtro_nome_pai').val();
                var e  = $('#filtro_escola').val();
                var di = $('#filtro_data_inicio').val();
                var df = $('#filtro_data_fim').val();
                var a  = $('#filtro_ano').val();
                var c  = $('#filtro_categoria').val();
                var pp = $('#por_pagina').val();
                if (n)  { args.filtro_nome_aluno = n; }
                if (p)  { args.filtro_nome_pai = p; }
                if (e)  { args.filtro_escola = e; }
                if (di) { args.filtro_data_inicio = di; }
                if (df) { args.filtro_data_fim = df; }
                if (a)  { args.filtro_ano = a; }
                if (c)  { args.filtro_categoria = c; }
                if (pp && pp !== '25') { args.por_pagina = pp; }
                var q = $.param(args);
                location.href = q ? (baseUrl + '&' + q) : baseUrl;
            }

            // Nome do aluno: pesquisa com pausa curta para não recarregar a cada tecla.
            $('#filtro_nome_aluno').on('keyup input', function () {
                clearTimeout(timer);
                timer = setTimeout(navegarPara, 600);
            });

            // Nome do pai: mesmo comportamento de busca com pausa.
            $('#filtro_nome_pai').on('keyup input', function () {
                clearTimeout(timer);
                timer = setTimeout(navegarPara, 600);
            });

            // Escola / datas / ano / categoria / por página: filtram imediatamente (vazio = volta a listar todas).
            $('#filtro_escola, #filtro_data_inicio, #filtro_data_fim, #filtro_ano, #filtro_categoria, #por_pagina').on('change', navegarPara);

            // Ano: pesquisa com pausa curta também.
            $('#filtro_ano').on('keyup input', function () {
                clearTimeout(timer);
                timer = setTimeout(navegarPara, 600);
            });

            // Botão Limpar: recarrega sem filtros.
            $('#apreas-filtros').on('click', '.apreas-btn-clear', function (e) {
                e.preventDefault();
                location.href = baseUrl;
            });

            function atualizarMetrica(key, delta) {
                var $v = $('.apreas-metric[data-metric="' + key + '"] .apreas-metric-value');
                if ($v.length) {
                    $v.text(Math.max(0, (parseInt($v.text(), 10) || 0) + delta));
                }
            }

            function mostrarStatus(txt, erro) {
                $status.text(txt).toggleClass('erro', !!erro).css('display', txt ? 'block' : 'none');
            }

            // Toggle Feito / Entregue
            $(document).on('change', '.apreas-pedido-check', function () {
                var $el   = $(this);
                var order = $el.data('order');
                var campo = $el.data('campo');
                var valor = $el.prop('checked') ? '1' : '0';
                var textoOn  = campo === 'feito' ? 'Feito' : 'Entregue';
                var textoOff = campo === 'feito' ? 'Não feito' : 'Pendente';

                $.post(ajaxurl, {
                    action:   'apreas_toggle_pedido',
                    nonce:    '<?php echo esc_js( wp_create_nonce( self::NONCE ) ); ?>',
                    order_id: order,
                    campo:    campo,
                    valor:    valor
                }, function (res) {
                    if (!res.success) {
                        $el.prop('checked', valor !== '1');
                        mostrarStatus('Erro ao salvar: ' + String(res.data || ''), true);
                        return;
                    }
                    var delta = valor === '1' ? 1 : -1;
                    atualizarMetrica(campo, delta);
                    if (campo === 'entregue') {
                        atualizarMetrica('pendente', -delta);
                    }

                    var $badge = $el.closest('td').find('.apreas-badge');
                    if ($badge.length) {
                        $badge
                            .toggleClass('badge-sim', valor === '1')
                            .toggleClass('badge-nao', valor !== '1')
                            .text(valor === '1' ? textoOn : textoOff);
                    }
                }).fail(function () {
                    $el.prop('checked', valor !== '1');
                    mostrarStatus('Erro de conexão ao salvar.', true);
                });
            });
        });
        </script>
        <?php
    }

    // ─────────────────────────────────────────────
    // CSRF + CSV
    // ─────────────────────────────────────────────

    /**
     * Intercepta o download do CSV no admin_init, ANTES de o WordPress
     * imprimir o HTML do admin. Assim o arquivo baixa apenas os dados,
     * sem código HTML da página.
     */
    public function processar_export_csv() {
        if ( ! current_user_can( self::CAPABILIDADE ) ) {
            return;
        }
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::SLUG ) {
            return;
        }
        if ( ! isset( $_GET['export'] ) || $_GET['export'] !== 'csv' ) {
            return;
        }

        $this->exportar_csv( $this->consultar_pedidos( -1 ) );
    }

    private function exportar_csv( $query ) {
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=relatorio-apreas-' . gmdate( 'Y-m-d-His' ) . '.csv' );

        $saida = fopen( 'php://output', 'w' );
        fwrite( $saida, "\xEF\xBB\xBF" ); // BOM p/ Excel

        fputcsv( $saida, [
            'Feito',
            'Entregue',
            'Nome do Pai',
            'Nome do Aluno',
            'WhatsApp',
            'Endereço Completo',
            'Escola',
            'Série',
            'Turma',
            'Pedidos Listados',
            'Data do Pedido',
            'Valor Total',
        ], ';' );

        while ( $query->have_posts() ) {
            $query->the_post();
            $order = wc_get_order( get_the_ID() );
            if ( ! $order ) {
                continue;
            }

            [ $serie, $turma ] = $this->quebrar_serie_turma( get_post_meta( $order->get_id(), '_apreas_turma', true ) );

            fputcsv( $saida, [
                ( get_post_meta( $order->get_id(), '_apreas_pedido_feito', true ) === '1' ) ? 'Sim' : 'Não',
                ( get_post_meta( $order->get_id(), '_apreas_pedido_entregue', true ) === '1' ) ? 'Sim' : 'Não',
                wp_strip_all_tags( $this->nome_responsavel( $order ) ),
                wp_strip_all_tags( get_post_meta( $order->get_id(), '_apreas_aluno', true ) ),
                wp_strip_all_tags( $order->get_billing_phone() ),
                wp_strip_all_tags( $this->endereco_completo( $order ) ),
                wp_strip_all_tags( get_post_meta( $order->get_id(), '_apreas_escola', true ) ),
                wp_strip_all_tags( $serie ),
                wp_strip_all_tags( $turma ),
                wp_strip_all_tags( $this->lista_pedidos( $order ) ),
                wp_strip_all_tags( $this->data_pedido( $order ) ),
                number_format( (float) $order->get_total(), 2, ',', '.' ),
            ], ';' );
        }

        fclose( $saida );
        exit;
    }

    // ─────────────────────────────────────────────
    // PÁGINA
    // ─────────────────────────────────────────────
    public function render_pagina() {
        if ( ! current_user_can( self::CAPABILIDADE ) ) {
            wp_die( 'Permissão negada.' );
        }

        nocache_headers();

        $nome_filtro      = $this->get_filtro_nome_aluno();
        $escola_filtro    = $this->get_filtro_escola();
        $pai_filtro       = $this->get_filtro_nome_pai();
        $data_inicio      = $this->get_filtro_data_inicio();
        $data_fim         = $this->get_filtro_data_fim();
        $ano_filtro       = $this->get_filtro_ano();
        $categoria_filtro = $this->get_filtro_categoria();
        $por_pagina       = $this->get_por_pagina();
        $escolas          = $this->listar_escolas_pedidos();
        $categorias       = $this->listar_categorias();
        $paged            = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $query            = $this->consultar_pedidos( $por_pagina, $paged );
        $stats            = $this->estatisticas();
        $filtros          = $this->argumentos_filtro();

        $url_base = admin_url( 'admin.php?page=' . self::SLUG );
        $url_csv  = add_query_arg( array_merge( [ 'export' => 'csv' ], $filtros ), $url_base );

        $this->estilos_script();
        ?>
        <div class="wrap apreas-relatorio-wrap" id="apreas-wrap" data-url-base="<?php echo esc_url( $url_base ); ?>">

            <!-- HEADER -->
            <div class="apreas-relatorio-header">
                <div class="apreas-header-left">
                    <span class="dashicons dashicons-clipboard apreas-header-icon"></span>
                    <div>
                        <h1>Relatório Avançado</h1>
                        <p>Controle de Produção e Entrega por aluno.</p>
                    </div>
                </div>
                <a href="<?php echo esc_url( $url_csv ); ?>" id="apreas-csv-link" class="apreas-btn apreas-btn-csv">
                    <span class="dashicons dashicons-download"></span> Exportar CSV
                </a>
            </div>

            <!-- STATUS -->
            <div class="apreas-status" id="apreas-status" role="status"></div>

            <!-- MÉTRICAS -->
            <?php echo $this->html_metricas( $stats ); ?>

            <!-- FILTROS -->
            <form method="get" class="apreas-filtros" id="apreas-filtros">
                <input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">

                <div class="apreas-filtro-campo">
                    <label for="filtro_nome_pai">Nome do Pai</label>
                    <input type="text" class="ctl" id="filtro_nome_pai" name="filtro_nome_pai"
                           value="<?php echo esc_attr( $pai_filtro ); ?>" placeholder="Pesquisar pelo pai...">
                </div>

                <div class="apreas-filtro-campo">
                    <label for="filtro_nome_aluno">Nome do Aluno</label>
                    <input type="text" class="ctl" id="filtro_nome_aluno" name="filtro_nome_aluno"
                           value="<?php echo esc_attr( $nome_filtro ); ?>" placeholder="Pesquisar aluno...">
                </div>

                <div class="apreas-filtro-campo">
                    <label for="filtro_escola">Escola</label>
                    <select id="filtro_escola" class="ctl" name="filtro_escola">
                        <option value="">Todas as Escolas</option>
                        <?php foreach ( $escolas as $esc ) : ?>
                            <option value="<?php echo esc_attr( $esc ); ?>" <?php selected( $escola_filtro, $esc ); ?>>
                                <?php echo esc_html( $esc ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="apreas-filtro-campo">
                    <label for="filtro_data_inicio">Data de Início</label>
                    <input type="date" class="ctl" id="filtro_data_inicio" name="filtro_data_inicio"
                           value="<?php echo esc_attr( $data_inicio ); ?>">
                </div>

                <div class="apreas-filtro-campo">
                    <label for="filtro_data_fim">Data de Fim</label>
                    <input type="date" class="ctl" id="filtro_data_fim" name="filtro_data_fim"
                           value="<?php echo esc_attr( $data_fim ); ?>">
                </div>

                <div class="apreas-filtro-campo">
                    <label for="filtro_ano">Ano</label>
                    <input type="text" class="ctl" id="filtro_ano" name="filtro_ano" maxlength="4" inputmode="numeric"
                           value="<?php echo esc_attr( $ano_filtro ); ?>" placeholder="Ex.: 2027" title="Informe apenas o ano">
                </div>

                <div class="apreas-filtro-campo">
                    <label for="filtro_categoria">Categoria de Produto</label>
                    <select id="filtro_categoria" class="ctl" name="filtro_categoria">
                        <option value="">Todas as Categorias</option>
                        <?php foreach ( $categorias as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $categoria_filtro, $cat->slug ); ?>>
                                <?php echo esc_html( $cat->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="apreas-filtro-campo">
                    <label for="por_pagina">Itens por Página</label>
                    <select id="por_pagina" class="ctl" name="por_pagina">
                        <?php foreach ( [ 10, 25, 50, 100 ] as $valor_pag ) : ?>
                            <option value="<?php echo esc_attr( $valor_pag ); ?>" <?php selected( $por_pagina, $valor_pag ); ?>>
                                <?php echo esc_html( $valor_pag ); ?> registros
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="apreas-filtro-acoes">
                    <button type="submit" class="apreas-btn apreas-btn-primary">Filtrar</button>
                    <a href="<?php echo esc_url( $url_base ); ?>" class="apreas-btn apreas-btn-clear">Limpar</a>
                </div>
            </form>

            <!-- RESULTADO -->
            <?php echo $this->html_conteudo( $query, $stats, $paged, $filtros ); ?>

            <?php wp_reset_postdata(); ?>
        </div>
        <?php
    }

    /**
     * Grade de métricas — reutilizada na página e no AJAX.
     */
    private function html_metricas( $stats ) {
        ob_start();
        ?>
        <div class="apreas-metrics" id="apreas-metricas">
            <div class="apreas-metric" data-metric="total">
                <span class="apreas-metric-icon m-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="m9 14 2 2 4-4"/></svg></span>
                <div>
                    <span class="apreas-metric-value"><?php echo number_format( $stats['total'], 0, ',', '.' ); ?></span>
                    <span class="apreas-metric-label">Total de Pedidos</span>
                </div>
            </div>
            <div class="apreas-metric" data-metric="feito">
                <span class="apreas-metric-icon m-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg></span>
                <div>
                    <span class="apreas-metric-value"><?php echo number_format( $stats['feito'], 0, ',', '.' ); ?></span>
                    <span class="apreas-metric-label">Feito</span>
                </div>
            </div>
            <div class="apreas-metric" data-metric="entregue">
                <span class="apreas-metric-icon m-indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg></span>
                <div>
                    <span class="apreas-metric-value"><?php echo number_format( $stats['entregue'], 0, ',', '.' ); ?></span>
                    <span class="apreas-metric-label">Entregue</span>
                </div>
            </div>
            <div class="apreas-metric" data-metric="pendente">
                <span class="apreas-metric-icon m-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                <div>
                    <span class="apreas-metric-value"><?php echo number_format( $stats['pendente'], 0, ',', '.' ); ?></span>
                    <span class="apreas-metric-label">Pendente de Entrega</span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Área de resultado (tabela ou estado vazio) — reutilizada na página e no AJAX.
     */
    private function html_conteudo( $query, $stats, $paged, $filtros ) {
        ob_start();

        if ( ! $query->have_posts() ) : ?>
            <div class="apreas-vazio" id="apreas-conteudo">
                <span class="dashicons dashicons-search"></span>
                <strong>Nenhum pedido encontrado.</strong>
                <span>Ajuste os filtros ou aguarde novos pedidos no WooCommerce.</span>
            </div>
        <?php else : ?>

            <!-- TABELA -->
            <div class="apreas-tabela-card" id="apreas-conteudo">
                <div class="apreas-tabela-top">
                    <span class="apreas-resultado"><strong><?php echo number_format( $stats['total'], 0, ',', '.' ); ?></strong> pedido(s) encontrado(s)</span>
                    <?php echo $this->paginacao( $query->max_num_pages, $paged, $filtros ); ?>
                </div>

                <div class="apreas-tabela-scroll">
                    <table class="apreas-tabela">
                        <thead>
                            <tr>
                                <th class="apreas-check">Feito</th>
                                <th class="apreas-check">Entregue</th>
                                <th>Nome do Pai</th>
                                <th>Nome do Aluno</th>
                                <th>Whatsapp</th>
                                <th>Endereço Completo</th>
                                <th>Escola</th>
                                <th>Série</th>
                                <th>Turma</th>
                                <th>Pedidos</th>
                                <th>Total</th>
                                <th>Data do Pedido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                                <?php
                                $order_id = get_the_ID();
                                $order    = wc_get_order( $order_id );
                                if ( ! $order ) {
                                    continue;
                                }

                                $feito    = get_post_meta( $order_id, '_apreas_pedido_feito', true ) === '1';
                                $entregue = get_post_meta( $order_id, '_apreas_pedido_entregue', true ) === '1';
                                $aluno    = get_post_meta( $order_id, '_apreas_aluno', true );
                                $escola_pedido = get_post_meta( $order_id, '_apreas_escola', true );
                                [ $serie, $turma ] = $this->quebrar_serie_turma( get_post_meta( $order_id, '_apreas_turma', true ) );
                                $link_pedido = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
                                $telefone    = trim( (string) $order->get_billing_phone() );
                                ?>
                                <tr>
                                    <td class="apreas-check">
                                        <input type="checkbox" class="apreas-pedido-check"
                                               data-order="<?php echo esc_attr( $order_id ); ?>" data-campo="feito"
                                               <?php checked( $feito ); ?> title="Marcar como feito">
                                        <span class="apreas-badge <?php echo $feito ? 'badge-sim' : 'badge-nao'; ?>">
                                            <?php echo $feito ? 'Feito' : 'Não feito'; ?>
                                        </span>
                                    </td>
                                    <td class="apreas-check">
                                        <input type="checkbox" class="apreas-pedido-check"
                                               data-order="<?php echo esc_attr( $order_id ); ?>" data-campo="entregue"
                                               <?php checked( $entregue ); ?> title="Marcar como entregue">
                                        <span class="apreas-badge <?php echo $entregue ? 'badge-sim' : 'badge-nao'; ?>">
                                            <?php echo $entregue ? 'Entregue' : 'Pendente'; ?>
                                        </span>
                                    </td>
                                    <td class="apreas-negrito"><?php echo esc_html( $this->nome_responsavel( $order ) ); ?></td>
                                    <td class="apreas-aluno"><?php echo esc_html( $aluno ); ?></td>
                                    <td>
                                        <?php if ( $telefone ) : ?>
                                            <a class="link-whatsapp" href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D/', '', $telefone ) ); ?>" target="_blank" rel="noopener">
                                                <span class="dashicons dashicons-phone"></span>
                                                <?php echo esc_html( $telefone ); ?>
                                            </a>
                                        <?php else : ?>—<?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html( $this->endereco_completo( $order ) ); ?></td>
                                    <td><?php echo esc_html( $escola_pedido ); ?></td>
                                    <td><?php echo esc_html( $serie ); ?></td>
                                    <td><?php echo esc_html( $turma ); ?></td>
                                    <td class="apreas-itens"><?php echo $this->lista_pedidos_html( $order ); ?></td>
                                    <td class="apreas-total"><?php echo wp_kses_post( $this->total_pedido( $order ) ); ?></td>
                                    <td class="apreas-data">
                                        <a class="link-pedido" href="<?php echo esc_url( $link_pedido ); ?>" title="Ver pedido nº <?php echo esc_attr( $order_id ); ?>">
                                            <span class="apreas-num-pedido">#<?php echo esc_html( $order_id ); ?></span>
                                            <?php echo esc_html( $this->data_pedido( $order ) ); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="apreas-tabela-bottom">
                    <span class="apreas-resultado">Página <?php echo (int) $paged; ?> de <?php echo (int) $query->max_num_pages; ?></span>
                    <?php echo $this->paginacao( $query->max_num_pages, $paged, $filtros ); ?>
                </div>
            </div>

        <?php endif;

        return ob_get_clean();
    }

    // ─────────────────────────────────────────────
    // AJAX — Filtra pedidos em tempo real
    // ─────────────────────────────────────────────
    public function ajax_filtrar_pedidos() {
        check_ajax_referer( self::NONCE, 'nonce' );

        if ( ! current_user_can( self::CAPABILIDADE ) ) {
            wp_send_json_error( 'Sem permissão' );
        }

        $paged  = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
        $filtros = $this->argumentos_filtro();

        $ids      = $this->ids_pedidos_filtrados();
        $query    = $this->consultar_pedidos( $this->get_por_pagina(), $paged );
        $stats    = $this->estatisticas();
        $conteudo = $this->html_conteudo( $query, $stats, $paged, $filtros );

        wp_send_json_success( [
            'metricas'      => $this->html_metricas( $stats ),
            'conteudo'      => $conteudo,
            'pagina_atual'  => $paged,
            'total_paginas' => (int) $query->max_num_pages,
            'depuracao'     => [
                'ids'           => count( $ids ),
                'post_count'    => (int) $query->post_count,
                'have_posts'    => (bool) $query->have_posts(),
                'total_paginas' => (int) $query->max_num_pages,
                'len_conteudo'  => strlen( $conteudo ),
            ],
        ] );
    }

    // ─────────────────────────────────────────────
    // AJAX — Alterna Feito / Entregue
    // ─────────────────────────────────────────────
    public function ajax_toggle_pedido() {
        check_ajax_referer( self::NONCE, 'nonce' );

        if ( ! current_user_can( self::CAPABILIDADE ) ) {
            wp_send_json_error( 'Sem permissão' );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $campo    = isset( $_POST['campo'] ) ? sanitize_key( $_POST['campo'] ) : '';
        $valor    = isset( $_POST['valor'] ) && rest_sanitize_boolean( wp_unslash( $_POST['valor'] ) ) ? '1' : '0';

        if ( ! $order_id || ! in_array( $campo, [ 'feito', 'entregue' ], true ) ) {
            wp_send_json_error( 'Parâmetros inválidos' );
        }

        if ( get_post_type( $order_id ) !== 'shop_order' ) {
            wp_send_json_error( 'Pedido inválido' );
        }

        update_post_meta( $order_id, '_apreas_pedido_' . $campo, $valor );
        wp_send_json_success( [ 'valor' => $valor ] );
    }
}

Relatorios::getInstance();