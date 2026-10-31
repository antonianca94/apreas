<?php
namespace Apreas;

if ( ! defined( 'ABSPATH' ) ) exit;

class Series {
    private static $instance;

    const MIGRATION_OPTION = 'apreas_series_migration_v1';

    public static function getInstance() {
        if (self::$instance == NULL) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'init', [$this, 'series_posttype'] );
        add_action( 'admin_init', [$this, 'adicionar_capacidades_para_administrador'], 20 );
        add_action( 'admin_init', [$this, 'migrar_turmas_para_series'], 30 );
        add_action( 'admin_init', [$this, 'atualizar_rewrite_rules'], 40 );
        add_action( 'add_meta_boxes', [$this, 'adicionar_meta_box_series'] );
        add_action( 'save_post', [$this, 'salvar_meta_box_series'] );
    }

    function series_posttype() {
        $labels = array(
            'name'                => 'Séries',
            'singular_name'       => 'Série',
            'menu_name'           => 'Séries',
            'all_items'           => 'Todas as Séries',
            'view_item'           => 'Ver Série',
            'add_new_item'        => 'Adicionar nova série',
            'add_new'             => 'Adicionar nova',
            'edit_item'           => 'Editar Série',
            'update_item'         => 'Atualizar Série',
            'search_items'        => 'Procurar Séries',
            'not_found'           => 'Não encontrado',
            'not_found_in_trash'  => 'Não encontrado no lixo',
        );

        $args = array(
            'label'               => 'Séries',
            'description'         => 'Inclua novas Séries',
            'labels'              => $labels,
            'menu_icon'           => 'dashicons-book-alt',
            'supports'            => array('title', 'content'),
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
            'show_in_rest'        => true,
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rest_base'           => 'series',
            'capabilities' => array(
                'edit_post'              => 'edit_series',
                'read_post'              => 'read_series',
                'delete_post'            => 'delete_series',
                'create_posts'           => 'create_series',
                'edit_posts'             => 'edit_series',
                'edit_others_posts'      => 'manage_series',
                'publish_posts'          => 'manage_series',
                'read_private_posts'     => 'read',
                'read'                   => 'read',
                'delete_posts'           => 'manage_series',
                'delete_private_posts'   => 'manage_series',
                'delete_published_posts' => 'manage_series',
                'delete_others_posts'    => 'manage_series',
                'edit_private_posts'     => 'edit_series',
                'edit_published_posts'   => 'edit_series'
            )
        );

        register_post_type( 'series', $args );
    }

    function adicionar_meta_box_series() {
        add_meta_box(
            'meta-box-series',
            'Informações da Série',
            [$this, 'exibir_meta_box_series'],
            'series',
            'normal',
            'high'
        );
    }

    function exibir_meta_box_series($post) {
        $nome = get_post_meta($post->ID, 'nome', true);
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var titleInput = document.getElementById('title');
                var nomeInput = document.getElementById('nome');
                var titleLabel = document.getElementById('title-prompt-text');
                if (!titleInput || !nomeInput) return;

                titleInput.addEventListener('input', function() {
                    nomeInput.value = titleInput.value;
                    if (titleLabel) titleLabel.style.display = titleInput.value === '' ? 'block' : 'none';
                });
                nomeInput.addEventListener('input', function() {
                    titleInput.value = nomeInput.value;
                    if (titleLabel) titleLabel.style.display = nomeInput.value === '' ? 'block' : 'none';
                });
            });
        </script>
        <?php wp_nonce_field('apreas_save_series', 'apreas_series_nonce'); ?>
        <div class="row mt-4 mb-4">
            <div class="col">
                <div class="form-group">
                    <label for="nome" class="mb-1 fw-bold">Nome</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?php echo esc_attr($nome); ?>" />
                </div>
            </div>
        </div>
        <?php
    }

    function salvar_meta_box_series($post_id) {
        if (get_post_type($post_id) !== 'series') return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['apreas_series_nonce']) || !wp_verify_nonce($_POST['apreas_series_nonce'], 'apreas_save_series')) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['nome'])) {
            update_post_meta($post_id, 'nome', sanitize_text_field($_POST['nome']));
        }

        $alunos = get_posts(array(
            'post_type'      => 'alunos',
            'post_status'    => array('publish', 'draft', 'private', 'pending'),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_key'       => 'serie',
            'meta_value'     => $post_id,
        ));

        foreach ($alunos as $aluno_id) {
            update_post_meta($aluno_id, 'serie_nome', get_the_title($post_id));
        }
    }

    public static function adicionar_capacidades_para_administrador() {
        $administrator_role = get_role('administrator');
        if (!$administrator_role) return;

        $administrator_role->add_cap('edit_series');
        $administrator_role->add_cap('read_series');
        $administrator_role->add_cap('delete_series');
        $administrator_role->add_cap('create_series');
        $administrator_role->add_cap('manage_series');
    }

    public function atualizar_rewrite_rules() {
        if (get_option('apreas_series_rewrite_rules') === '1') return;

        flush_rewrite_rules();
        update_option('apreas_series_rewrite_rules', '1');
    }

    /**
     * Separa um rótulo combinado (ex.: "3º Ano A") em série e turma.
     */
    public static function quebrar_serie_turma($valor) {
        $valor = trim((string) $valor);
        if ($valor === '') return ['', ''];

        if ( preg_match('/^(turma|sala)\b/iu', $valor)
            || preg_match('/^(manh[ãa]|tarde|noite|integral)$/iu', $valor)
            || preg_match('/^\d{1,2}$/iu', $valor) ) {
            return ['', $valor];
        }

        if ( preg_match('/^(.+?(?:Ano|S[eé]rie|Per[ií]odo|Fase)[\.:]?)\s*(.*)$/iu', $valor, $m) ) {
            $serie = trim($m[1]);
            $turma = trim($m[2], " \t\n\r\0\x0B-–");
            if ($turma !== '') return [$serie, $turma];
        }

        // "Maternal B", "Berçário A", "Pré II A", "Infantil 3 B"
        if ( preg_match('/^((?:Maternal|Ber[cç][aá]rio|Pr[eé]|Infantil|Fundamental|M[eé]dio)\b.*?)\s+([A-Za-z])$/iu', $valor, $m) ) {
            return [trim($m[1]), trim($m[2])];
        }

        if ( preg_match('/^(.+\d[º°ª]?)\s+([A-Za-zÀ-ÖØ-öø-ÿ]+)$/iu', $valor, $m) ) {
            $serie = trim($m[1]);
            $turma = trim($m[2]);
            if ( preg_match('/(Ano|S[eé]rie|Per[ií]odo|Fase)/iu', $serie) ) {
                return [$serie, $turma];
            }
        }

        return [$valor, ''];
    }

    /**
     * Retorna o ID de uma série pelo título, criando-a quando necessário.
     */
    public static function encontrar_serie_por_titulo($titulo) {
        $titulo = trim((string) $titulo);
        if ($titulo === '') return 0;

        $series = get_posts(array(
            'post_type'      => 'series',
            'post_status'    => array('publish', 'draft'),
            'title'          => $titulo,
            'posts_per_page' => 1,
            'fields'         => 'ids'
        ));

        return !empty($series) ? (int) $series[0] : 0;
    }

    public static function obter_serie_por_titulo($titulo) {
        $titulo = trim((string) $titulo);
        if ($titulo === '') return 0;

        $serie_id = self::encontrar_serie_por_titulo($titulo);
        if ($serie_id) return $serie_id;

        $serie_id = wp_insert_post(array(
            'post_type'   => 'series',
            'post_status' => 'publish',
            'post_title'  => $titulo,
            'post_name'   => sanitize_title($titulo)
        ), true);

        if (is_wp_error($serie_id) || !$serie_id) return 0;

        update_post_meta($serie_id, 'nome', $titulo);
        return (int) $serie_id;
    }

    /**
     * Migra os registros antigos de Turmas para Séries e atualiza os Alunos.
     *
     * O CPT legado "turmas" é mantido para não alterar as demais áreas do
     * plugin. Cada aluno passa a guardar o ID da série em "serie" e o texto
     * da turma em "turma".
     */
    public function migrar_turmas_para_series() {
        if (get_option(self::MIGRATION_OPTION) === '1') return;

        $turmas = get_posts(array(
            'post_type'      => 'turmas',
            'post_status'    => array('publish', 'draft', 'private', 'pending'),
            'posts_per_page' => -1
        ));

        $mapa = array();
        $falhou_migracao = false;

        foreach ($turmas as $turma) {
            list($serie_nome, $turma_nome) = self::quebrar_serie_turma($turma->post_title);
            if ($serie_nome === '') {
                $serie_nome = $turma->post_title;
                $turma_nome = '';
            }

            $serie_id = self::obter_serie_por_titulo($serie_nome);
            if (!$serie_id) {
                $falhou_migracao = true;
                continue;
            }

            $origens = get_post_meta($serie_id, '_apreas_turmas_origem_id', true);
            $origens = is_array($origens) ? $origens : array();
            $origens[] = (int) $turma->ID;
            update_post_meta($serie_id, '_apreas_turmas_origem_id', array_values(array_unique($origens)));

            $dados = array(
                'serie' => $serie_id,
                'turma' => $turma_nome
            );
            $mapa['id:' . (int) $turma->ID] = $dados;
            $mapa['titulo:' . $turma->post_title] = $dados;
            $mapa['slug:' . $turma->post_name] = $dados;
        }

        if (!empty($mapa)) {
            $alunos = get_posts(array(
                'post_type'      => 'alunos',
                'post_status'    => array('publish', 'draft', 'private', 'pending'),
                'posts_per_page' => -1,
                'fields'         => 'ids'
            ));

            foreach ($alunos as $aluno_id) {
                $turma_antiga = get_post_meta($aluno_id, 'turma', true);
                if (!is_scalar($turma_antiga) || $turma_antiga === '') continue;

                $serie_atual = absint(get_post_meta($aluno_id, 'serie', true));
                if ($serie_atual && get_post_type($serie_atual) === 'series') {
                    if (!get_post_meta($aluno_id, 'serie_nome', true)) {
                        update_post_meta($aluno_id, 'serie_nome', get_the_title($serie_atual));
                    }
                    continue;
                }

                $chave = (string) $turma_antiga;
                if (ctype_digit($chave) && isset($mapa['id:' . (int) $chave])) {
                    $dados = $mapa['id:' . (int) $chave];
                } elseif (isset($mapa['titulo:' . $chave])) {
                    $dados = $mapa['titulo:' . $chave];
                } elseif (isset($mapa['slug:' . $chave])) {
                    $dados = $mapa['slug:' . $chave];
                } else {
                    continue;
                }

                update_post_meta($aluno_id, '_apreas_turma_legacy_valor', $turma_antiga);
                update_post_meta($aluno_id, 'serie', $dados['serie']);
                update_post_meta($aluno_id, 'serie_nome', get_the_title($dados['serie']));
                update_post_meta($aluno_id, 'turma', $dados['turma']);
            }
        }

        if (!$falhou_migracao) {
            update_option(self::MIGRATION_OPTION, '1');
        }
    }
}

Series::getInstance();
