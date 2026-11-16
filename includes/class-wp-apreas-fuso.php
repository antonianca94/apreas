<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Fuso único do plugin: relógio de Brasília (America/Sao_Paulo, UTC-3).
 *
 * Os horários gerados e exibidos pelo plugin não dependem do fuso configurado
 * no WordPress: ancoramos tudo em Brasília para que o evento no Google Calendar,
 * os carimbos de envio/teste e a data do pedido batam com o relógio local.
 */
class Fuso {
    const BRASILIA = 'America/Sao_Paulo';

    private static $zona = null;

    public static function zona() {
        if ( self::$zona === null ) {
            self::$zona = new \DateTimeZone( self::BRASILIA );
        }
        return self::$zona;
    }

    /**
     * Agora no relógio de Brasília, no formato mysql (Y-m-d H:i:s).
     * É um horário de parede: ao reexibir, basta reformatar a string, sem
     * converter de novo, que o valor continua sendo o de Brasília.
     */
    public static function agora() {
        return ( new \DateTimeImmutable( 'now', self::zona() ) )->format( 'Y-m-d H:i:s' );
    }
}