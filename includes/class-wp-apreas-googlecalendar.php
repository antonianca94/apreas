<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

class GoogleCalendar {
    const OPTION_CALENDAR_ID = 'apreas_gcal_calendar_id';
    const OPTION_SERVICE_JSON = 'apreas_gcal_service_json';
    const OPTION_ENABLED     = 'apreas_gcal_enabled';
    const OPTION_ULTIMO_TESTE = 'apreas_gcal_ultimo_teste';

    const META_EVENT_ID = '_apreas_gcal_event_id';
    const META_STATUS   = '_apreas_gcal_status';
    const META_ERRO     = '_apreas_gcal_erro';
    const META_SYNC     = '_apreas_gcal_synced_at';
    const META_LINK     = '_apreas_gcal_link';

    const TOKEN_URL      = 'https://oauth2.googleapis.com/token';
    const API_BASE       = 'https://www.googleapis.com/calendar/v3/calendars/';
    const SCOPE          = 'https://www.googleapis.com/auth/calendar';
    const TRANSIENT_TOKEN = 'apreas_gcal_access_token';

    private static $instance;
    private $credenciais = null;

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'save_post_' . Eventos::POST_TYPE, [ $this, 'ao_salvar' ], 20, 2 );
    }

    public function calendar_id() {
        return sanitize_text_field( get_option( self::OPTION_CALENDAR_ID, '' ) );
    }

    public function configurado() {
        return $this->calendar_id() !== '' && $this->credenciais() !== null;
    }

    public function ativo() {
        return $this->configurado() && (bool) get_option( self::OPTION_ENABLED, 1 );
    }

    private function credenciais() {
        if ( $this->credenciais !== null ) {
            return $this->credenciais;
        }

        $json = json_decode( (string) get_option( self::OPTION_SERVICE_JSON, '' ), true );

        $this->credenciais = ( is_array( $json ) && ! empty( $json['client_email'] ) && ! empty( $json['private_key'] ) )
            ? $json
            : false;

        return $this->credenciais;
    }

    public function ao_salvar( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;
        if ( ! isset( $_POST[ Eventos::NONCE_CAMPO ] ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        if ( $post->post_status !== 'publish' ) return;

        $this->sincronizar( $post_id );
    }

    public function sincronizar( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== Eventos::POST_TYPE ) {
            return false;
        }

        if ( ! $this->ativo() ) {
            update_post_meta( $post_id, self::META_STATUS, 'off' );
            return false;
        }

        $evento = $this->montar_evento( $post_id );
        if ( is_wp_error( $evento ) ) {
            update_post_meta( $post_id, self::META_STATUS, 'sem_dados' );
            update_post_meta( $post_id, self::META_ERRO, $evento->get_error_message() );
            return false;
        }

        $base     = self::API_BASE . rawurlencode( $this->calendar_id() ) . '/events';
        $event_id = get_post_meta( $post_id, self::META_EVENT_ID, true );

        if ( $event_id ) {
            $resultado = $this->requisicao( 'PATCH', $base . '/' . rawurlencode( $event_id ), $evento );

            if ( is_wp_error( $resultado ) && in_array( $this->codigo_http( $resultado ), [ 404, 410 ], true ) ) {
                $resultado = $this->requisicao( 'POST', $base, $evento );
            }
        } else {
            $resultado = $this->requisicao( 'POST', $base, $evento );
        }

        if ( is_wp_error( $resultado ) ) {
            $mensagem = $resultado->get_error_message();
            update_post_meta( $post_id, self::META_STATUS, 'erro' );
            update_post_meta( $post_id, self::META_ERRO, function_exists( 'mb_substr' ) ? mb_substr( $mensagem, 0, 300 ) : substr( $mensagem, 0, 300 ) );
            return false;
        }

        if ( ! empty( $resultado['id'] ) ) {
            update_post_meta( $post_id, self::META_EVENT_ID, sanitize_text_field( $resultado['id'] ) );
        }
        update_post_meta( $post_id, self::META_LINK, isset( $resultado['htmlLink'] ) ? esc_url_raw( $resultado['htmlLink'] ) : '' );
        update_post_meta( $post_id, self::META_STATUS, 'ok' );
        update_post_meta( $post_id, self::META_ERRO, '' );
        update_post_meta( $post_id, self::META_SYNC, Fuso::agora() );

        return true;
    }

    private function montar_evento( $post_id ) {
        $post  = get_post( $post_id );
        $data  = get_post_meta( $post_id, 'data', true );
        $hora  = get_post_meta( $post_id, 'horario_inicio', true );
        $horas = get_post_meta( $post_id, 'quantidade_horas', true );

        if ( $data === '' || $hora === '' || $horas === '' || (float) $horas <= 0 ) {
            return new \WP_Error( 'sem_dados', 'Preencha Data, Horário Início e Quantidade de Horas para enviar ao Google Calendar.' );
        }

        $minutos = (int) round( (float) $horas * 60 );
        if ( $minutos < 1 ) {
            return new \WP_Error( 'sem_dados', 'A Quantidade de Horas precisa ser maior que zero.' );
        }

        try {
            $inicio = new \DateTimeImmutable( $data . ' ' . $hora, Fuso::zona() );
            $fim    = $inicio->modify( '+' . $minutos . ' minutes' );
        } catch ( \Exception $e ) {
            return new \WP_Error( 'data_invalida', 'Data ou Horário Início inválidos.' );
        }

        $titulo = $post->post_title !== '' ? $post->post_title : 'Evento #' . $post_id;

        return [
            'summary'     => $titulo,
            'location'    => (string) get_post_meta( $post_id, 'local_evento', true ),
            'description' => $this->montar_descricao( $post_id ),
            'start'       => $this->momento( $inicio ),
            'end'         => $this->momento( $fim ),
        ];
    }

    private function momento( \DateTimeImmutable $momento ) {
        $dados = [ 'dateTime' => $momento->format( 'c' ) ];
        $fuso  = $momento->getTimezone()->getName();

        if ( strpos( $fuso, '/' ) !== false ) {
            $dados['timeZone'] = $fuso;
        }

        return $dados;
    }

    private function montar_descricao( $post_id ) {
        $linhas = [];

        $textos = [
            'tipo_evento'         => 'Tipo de evento',
            'cliente_responsavel' => 'Cliente responsável',
            'nome_fotografado'    => 'Nome a ser fotografado',
            'idade'               => 'Idade',
            'forma_entrega'       => 'Forma de entrega',
            'forma_pagamento'     => 'Forma de pagamento',
        ];
        foreach ( $textos as $campo => $rotulo ) {
            $valor = get_post_meta( $post_id, $campo, true );
            if ( $valor !== '' ) {
                $linhas[] = $rotulo . ': ' . $valor;
            }
        }

        $valores = [
            'valor_total'    => 'Valor total cobrado',
            'valor_terceiro' => 'Valor terceiro',
            'valor_receber'  => 'Valor a receber',
            'valor_pendrive' => 'Valor pendrive',
        ];
        foreach ( $valores as $campo => $rotulo ) {
            $valor = get_post_meta( $post_id, $campo, true );
            if ( $valor !== '' ) {
                $linhas[] = $rotulo . ': R$ ' . number_format( (float) $valor, 2, ',', '.' );
            }
        }

        foreach ( Eventos::controle_opcoes() as $item ) {
            $rotulo   = rtrim( $item['rotulo'], '?' );
            $linhas[] = $rotulo . ': ' . ( get_post_meta( $post_id, $item['chave'], true ) === '1' ? 'Sim' : 'Não' );
        }

        $observacoes = get_post_meta( $post_id, 'observacoes', true );
        if ( $observacoes !== '' ) {
            $linhas[] = '';
            $linhas[] = 'Descrição:';
            $linhas[] = $observacoes;
        }

        $linhas[] = '';
        $linhas[] = 'Registro: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' );

        return implode( "\n", $linhas );
    }

    private function codigo_http( $erro ) {
        $dados = $erro->get_error_data();
        return ( is_array( $dados ) && isset( $dados['status'] ) ) ? (int) $dados['status'] : 0;
    }

    private function base64url( $dados ) {
        return rtrim( strtr( base64_encode( $dados ), '+/', '-_' ), '=' );
    }

    private function get_access_token() {
        $token = get_transient( self::TRANSIENT_TOKEN );
        if ( is_string( $token ) && $token !== '' ) {
            return $token;
        }

        $credenciais = $this->credenciais();
        if ( ! $credenciais ) {
            return new \WP_Error( 'sem_credenciais', 'Chave JSON da service account inválida ou ausente.' );
        }

        $token = $this->pedir_token( $credenciais );
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        set_transient( self::TRANSIENT_TOKEN, $token, 3000 );

        return $token;
    }

    /**
     * Pede um token novo para as credenciais dadas, sem ler nem gravar o cache.
     * O teste de conexão usa isto para não validar a chave errada quando o
     * formulário tem valores ainda não salvos.
     */
    private function pedir_token( $credenciais ) {
        if ( ! function_exists( 'openssl_sign' ) ) {
            return new \WP_Error( 'sem_openssl', 'A extensão OpenSSL do PHP não está disponível neste servidor.' );
        }

        $agora  = time();
        $header = $this->base64url( wp_json_encode( [ 'alg' => 'RS256', 'typ' => 'JWT' ] ) );
        $claims = $this->base64url( wp_json_encode( [
            'iss'   => $credenciais['client_email'],
            'scope' => self::SCOPE,
            'aud'   => self::TOKEN_URL,
            'iat'   => $agora,
            'exp'   => $agora + 3600,
        ] ) );

        $assinatura = '';
        $ok = openssl_sign( $header . '.' . $claims, $assinatura, $credenciais['private_key'], OPENSSL_ALGO_SHA256 );
        if ( ! $ok ) {
            return new \WP_Error( 'jwt_falhou', 'Não foi possível assinar o JWT. Verifique a private_key da chave JSON.' );
        }

        $resposta = wp_remote_post( self::TOKEN_URL, [
            'timeout' => 20,
            'body'    => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $header . '.' . $claims . '.' . $this->base64url( $assinatura ),
            ],
        ] );

        if ( is_wp_error( $resposta ) ) {
            return new \WP_Error( 'token_falhou', 'Falha ao contatar o Google: ' . $resposta->get_error_message() );
        }

        $codigo = (int) wp_remote_retrieve_response_code( $resposta );
        $dados  = json_decode( wp_remote_retrieve_body( $resposta ), true );

        if ( $codigo !== 200 || empty( $dados['access_token'] ) ) {
            $motivo = isset( $dados['error_description'] ) ? $dados['error_description'] : 'resposta HTTP ' . $codigo;
            return new \WP_Error( 'token_negado', 'Google recusou a autenticação: ' . $motivo );
        }

        return sanitize_text_field( $dados['access_token'] );
    }

    private function requisicao( $metodo, $url, $corpo = null, $token_fixo = null ) {
        $token = $token_fixo !== null ? $token_fixo : $this->get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        for ( $tentativa = 0; $tentativa < 2; $tentativa ++ ) {
            $args = [
                'method'  => $metodo,
                'timeout' => 25,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json; charset=utf-8',
                ],
            ];
            if ( $corpo !== null ) {
                $args['body'] = wp_json_encode( $corpo );
            }

            $resposta = wp_remote_request( $url, $args );
            if ( is_wp_error( $resposta ) ) {
                return new \WP_Error( 'conexao_falhou', 'Falha ao contatar o Google Calendar: ' . $resposta->get_error_message() );
            }

            $codigo = (int) wp_remote_retrieve_response_code( $resposta );
            $dados  = json_decode( wp_remote_retrieve_body( $resposta ), true );

            if ( $codigo === 401 && $tentativa === 0 && $token_fixo === null ) {
                delete_transient( self::TRANSIENT_TOKEN );
                $token = $this->get_access_token();
                if ( is_wp_error( $token ) ) {
                    return $token;
                }
                continue;
            }

            if ( $codigo >= 200 && $codigo < 300 ) {
                return is_array( $dados ) ? $dados : [];
            }

            $motivo = isset( $dados['error']['message'] ) ? $dados['error']['message'] : wp_remote_retrieve_response_message( $resposta );

            return new \WP_Error(
                'gcal_http_' . $codigo,
                'Google Calendar respondeu ' . $codigo . ': ' . $motivo,
                [ 'status' => $codigo ]
            );
        }

        return new \WP_Error( 'gcal_falhou', 'Não foi possível concluir a chamada ao Google Calendar.' );
    }

    /**
     * Testa a conexão com os valores vindos do formulário (ainda não salvos):
     * autentica com a chave dada e lê o calendário. Não grava opções nem o
     * cache de token, e não cria nenhum evento.
     */
    public function testar_com( $json, $calendar_id ) {
        $calendar_id = sanitize_text_field( (string) $calendar_id );
        $json        = trim( (string) $json );

        if ( $calendar_id === '' ) {
            return new \WP_Error( 'sem_calendar_id', 'Informe o Calendar ID no passo 2 antes de testar.' );
        }

        $credenciais = json_decode( $json, true );
        if ( ! is_array( $credenciais ) || empty( $credenciais['client_email'] ) || empty( $credenciais['private_key'] ) ) {
            return new \WP_Error( 'json_invalido', 'Chave JSON inválida: cole o arquivo completo da service account, com client_email e private_key (passo 1).' );
        }
        if ( isset( $credenciais['type'] ) && $credenciais['type'] !== 'service_account' ) {
            return new \WP_Error( 'json_tipo', 'Este JSON não é de uma service account (type "' . sanitize_text_field( $credenciais['type'] ) . '"). Crie a chave em IAM e administrador → Contas de serviço → Chaves.' );
        }

        $token = $this->pedir_token( $credenciais );
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $resultado = $this->requisicao( 'GET', self::API_BASE . rawurlencode( $calendar_id ), null, $token );
        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        return [
            'id'           => isset( $resultado['id'] ) ? sanitize_text_field( $resultado['id'] ) : $calendar_id,
            'summary'      => isset( $resultado['summary'] ) ? sanitize_text_field( $resultado['summary'] ) : $calendar_id,
            'timeZone'     => isset( $resultado['timeZone'] ) ? sanitize_text_field( $resultado['timeZone'] ) : '',
            'accessRole'   => isset( $resultado['accessRole'] ) ? sanitize_text_field( $resultado['accessRole'] ) : '',
            'client_email' => sanitize_email( $credenciais['client_email'] ),
        ];
    }

    public function ultimo_teste() {
        $registro = get_option( self::OPTION_ULTIMO_TESTE, null );
        return is_array( $registro ) ? $registro : null;
    }

    public function registrar_teste( $resultado ) {
        update_option( self::OPTION_ULTIMO_TESTE, [
            'quando'       => Fuso::agora(),
            'summary'      => isset( $resultado['summary'] ) ? $resultado['summary'] : '',
            'calendar_id'  => isset( $resultado['id'] ) ? $resultado['id'] : '',
            'accessRole'   => isset( $resultado['accessRole'] ) ? $resultado['accessRole'] : '',
            'client_email' => isset( $resultado['client_email'] ) ? $resultado['client_email'] : '',
        ], false );
    }

    public function esquecer_teste() {
        delete_option( self::OPTION_ULTIMO_TESTE );
    }
}