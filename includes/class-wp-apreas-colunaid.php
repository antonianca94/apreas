<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Coluna ID com botão de copiar, na mesma estrutura em todas as listagens.
 * O filtro roda em prioridade 20 porque a listagem de Alunos remonta o array
 * de colunas em prioridade 10 e descartaria uma coluna inserida antes.
 */
class ColunaId {
    const COLUNA     = 'post_id';
    const POST_TYPES = [ 'unidades', 'escolas', 'series', 'alunos' ];

    private static $instance;

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        foreach ( self::POST_TYPES as $post_type ) {
            add_filter( "manage_{$post_type}_posts_columns", [ $this, 'colunas' ], 20 );
            add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'conteudo' ], 10, 2 );
        }

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function colunas( $columns ) {
        if ( isset( $columns[ self::COLUNA ] ) ) {
            return $columns;
        }

        $novas = [];
        foreach ( $columns as $chave => $rotulo ) {
            $novas[ $chave ] = $rotulo;
            if ( $chave === 'cb' ) {
                $novas[ self::COLUNA ] = 'ID';
            }
        }

        if ( ! isset( $novas[ self::COLUNA ] ) ) {
            return [ self::COLUNA => 'ID' ] + $novas;
        }

        return $novas;
    }

    public function conteudo( $column, $post_id ) {
        if ( $column !== self::COLUNA ) {
            return;
        }

        $post_id = absint( $post_id );

        printf(
            '<button type="button" class="apreas-copy-id" data-apreas-copy-id="%1$d" title="Copiar ID" aria-label="Copiar ID %1$d"><strong>%1$d</strong><span class="dashicons dashicons-clipboard" aria-hidden="true"></span></button>',
            $post_id
        );
    }

    public function enqueue_assets( $hook ) {
        if ( $hook !== 'edit.php' ) {
            return;
        }

        $tela = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $tela || ! in_array( $tela->post_type, self::POST_TYPES, true ) ) {
            return;
        }

        wp_enqueue_script(
            'apreas-copy-id',
            plugins_url( '/admin/js/copy-id.js', dirname( __DIR__ ) . '/apreas.php' ),
            [],
            '1.0.0',
            true
        );
    }
}