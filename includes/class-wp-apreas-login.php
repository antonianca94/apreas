<?php
namespace Apreas;
    session_start();

if ( ! defined( 'ABSPATH' ) ) exit;

class Login {
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {      
        add_shortcode( 'login_form', [$this,'render_login_form'] );
        add_action('wp_ajax_process_login_form', [$this,'process_login_form']);
        add_action('wp_ajax_nopriv_process_login_form', [$this,'process_login_form']);

        add_shortcode( 'login_form_eventos', [$this,'render_login_form_eventos'] );
        add_action('wp_ajax_process_login_form_eventos', [$this,'process_login_form_eventos']);
        add_action('wp_ajax_nopriv_process_login_form_eventos', [$this,'process_login_form_eventos']);

        add_shortcode( 'galeria', [$this,'shortcode_fotos_container'] );
        add_shortcode( 'fotos_selecionadas', [$this,'fotos_counter'] );

        // LOGOS
        add_shortcode('imagem_logo_evento', [$this,'mostrar_imagem_logo_evento']);
        add_shortcode('imagem_logo_escola', [$this,'mostrar_imagem_logo_escola']);
        // LOGOS

        // ESCOLAS
        add_shortcode('lotes_escola', [$this,'mostrar_lotes_escolas']);
        // ESCOLAS

        // EVENTOS
        add_shortcode('lotes_evento', [$this,'mostrar_lotes_eventos']);
        // EVENTOS

        // DADOS DO ALUNO
        add_shortcode('dados_aluno', [$this,'mostrar_dados_aluno']);
        // DADOS DO ALUNO
    }

    function mostrar_dados_aluno($atts) {
        $a = shortcode_atts( array(
            'cor' => '',
            'tamanho' => '',
            'peso' => '',
            'alinhar' => '',
            'fonte' => '',
            'espacamento' => '5px',
            'fundo' => '',
            'padding' => '0',
            'borda_raio' => '0',
            'cor_label' => '',
            'peso_label' => 'bold',
            'tamanho_label' => '',
            'label_nome' => 'Nome:',
            'label_escola' => 'Escola:',
            'label_turma' => 'Turma:',
            'label_unidade' => 'Unidade:',
            'label_data' => 'Data de Nascimento:',
            'mostrar_nome' => 'true',
            'mostrar_escola' => 'true',
            'mostrar_turma' => 'true',
            'mostrar_unidade' => 'true',
            'mostrar_data' => 'true'
        ), $atts );

        $container_style = 'display: none; ';
        if (!empty($a['alinhar'])) $container_style .= 'text-align: ' . esc_attr($a['alinhar']) . '; ';
        if (!empty($a['fonte'])) $container_style .= 'font-family: ' . esc_attr($a['fonte']) . '; ';
        if (!empty($a['fundo'])) $container_style .= 'background-color: ' . esc_attr($a['fundo']) . '; ';
        if (!empty($a['padding'])) $container_style .= 'padding: ' . esc_attr($a['padding']) . '; ';
        if (!empty($a['borda_raio'])) $container_style .= 'border-radius: ' . esc_attr($a['borda_raio']) . '; ';
        
        $p_style = 'margin-bottom: ' . esc_attr($a['espacamento']) . '; ';
        if (!empty($a['cor'])) $p_style .= 'color: ' . esc_attr($a['cor']) . '; ';
        if (!empty($a['tamanho'])) $p_style .= 'font-size: ' . esc_attr($a['tamanho']) . '; ';
        
        $span_style = '';
        if (!empty($a['peso'])) $span_style .= 'font-weight: ' . esc_attr($a['peso']) . '; ';

        $label_style = '';
        if (!empty($a['cor_label'])) $label_style .= 'color: ' . esc_attr($a['cor_label']) . '; ';
        if (!empty($a['peso_label'])) $label_style .= 'font-weight: ' . esc_attr($a['peso_label']) . '; ';
        if (!empty($a['tamanho_label'])) $label_style .= 'font-size: ' . esc_attr($a['tamanho_label']) . '; ';

        ob_start();
        ?>
        <div class="dados-aluno-container" style="<?php echo $container_style; ?>">
            <?php if ($a['mostrar_nome'] !== 'false' && $a['mostrar_nome'] !== '0') : ?>
                <p style="<?php echo $p_style; ?>"><strong style="<?php echo $label_style; ?>"><?php echo esc_html($a['label_nome']); ?></strong> <span class="aluno-nome" style="<?php echo $span_style; ?>"></span></p>
            <?php endif; ?>
            <?php if ($a['mostrar_escola'] !== 'false' && $a['mostrar_escola'] !== '0') : ?>
                <p style="<?php echo $p_style; ?>"><strong style="<?php echo $label_style; ?>"><?php echo esc_html($a['label_escola']); ?></strong> <span class="aluno-escola" style="<?php echo $span_style; ?>"></span></p>
            <?php endif; ?>
            <?php if ($a['mostrar_turma'] !== 'false' && $a['mostrar_turma'] !== '0') : ?>
                <p style="<?php echo $p_style; ?>"><strong style="<?php echo $label_style; ?>"><?php echo esc_html($a['label_turma']); ?></strong> <span class="aluno-turma" style="<?php echo $span_style; ?>"></span></p>
            <?php endif; ?>
            <?php if ($a['mostrar_unidade'] !== 'false' && $a['mostrar_unidade'] !== '0') : ?>
                <p style="<?php echo $p_style; ?>"><strong style="<?php echo $label_style; ?>"><?php echo esc_html($a['label_unidade']); ?></strong> <span class="aluno-unidade" style="<?php echo $span_style; ?>"></span></p>
            <?php endif; ?>
            <?php if ($a['mostrar_data'] !== 'false' && $a['mostrar_data'] !== '0') : ?>
                <p style="<?php echo $p_style; ?>"><strong style="<?php echo $label_style; ?>"><?php echo esc_html($a['label_data']); ?></strong> <span class="aluno-data-nascimento" style="<?php echo $span_style; ?>"></span></p>
            <?php endif; ?>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                try {
                    var raw = localStorage.getItem('apreas_login_sessao');
                    if (raw) {
                        var s = JSON.parse(raw);
                        if (Date.now() <= s.expira) {
                            var dados = s.dados;
                            if (s.tipo === 'eventos' && Array.isArray(dados) && dados.length > 0) {
                                dados = dados[0];
                            }
                            if (dados) {
                                var container = document.querySelector('.dados-aluno-container');
                                if (container) {
                                    container.style.display = 'block';
                                    var elNome = document.querySelector('.aluno-nome');
                                    var elEscola = document.querySelector('.aluno-escola');
                                    var elTurma = document.querySelector('.aluno-turma');
                                    var elUnidade = document.querySelector('.aluno-unidade');
                                    var elDataNasc = document.querySelector('.aluno-data-nascimento');

                                    if(elNome) elNome.textContent = dados.nome || '';
                                    if(elEscola && dados.escola) elEscola.textContent = dados.escola.nome || '';
                                    if(elTurma && dados.turma) elTurma.textContent = dados.turma.nome || '';
                                    if(elUnidade && dados.unidade) elUnidade.textContent = dados.unidade.nome || '';
                                    
                                    if(elDataNasc && dados.data_nascimento) {
                                        // converter YYYY-MM-DD para DD/MM/AAAA se vier no formato do banco
                                        var dn = dados.data_nascimento;
                                        if(dn.includes('-')) {
                                            var p = dn.split('-');
                                            if(p.length === 3) dn = p[2] + '/' + p[1] + '/' + p[0];
                                        }
                                        elDataNasc.textContent = dn;
                                    }
                                }
                            }
                        }
                    }
                } catch(e) { console.error("Erro ao carregar dados do aluno", e); }
            });
        </script>
        <?php
        return ob_get_clean();
    }


    function mostrar_lotes_eventos() {
        return '
            <div class="d-flex justify-content-start align-items-center">
                <div class="w-100 p-3 m-3" style="border: 1px solid grey; border-radius: 1rem;">
                    <h3 style="padding-bottom: 0.5rem; font-weight: 700;">Lote 1</h3>
                    <h5>Escolha</h5>
                    <p style="font-size: 1.35rem;"> <span class="l1_escolha_data_inicio_evento"> </span> à <span class="l1_escolha_data_fim_evento"> </span> </p>
                    <h5>Entrega</h5>
                    <p style="font-size: 1.35rem;"> <span class="l1_entrega_data_evento"> </span> </p>
                </div>
                <div class="w-100 p-3 m-3" style="border: 1px solid grey; border-radius: 1rem;">
                    <h3 style="padding-bottom: 0.5rem; font-weight: 700;">Lote 2</h3>
                    <h5>Escolha</h5>
                    <p style="font-size: 1.35rem;"> <span class="l2_escolha_data_inicio_evento"> </span> à <span class="l2_escolha_data_fim_evento"> </span> </p>
                    <h5>Entrega</h5>
                    <p style="font-size: 1.35rem;"> <span class="l2_entrega_data_evento"> </span> </p>
                </div>
            </div>
        ';
    }

    function mostrar_lotes_escolas() {
        return '
            <div class="d-flex justify-content-start align-items-center">
                <div class="w-100 p-3 m-3" style="border: 1px solid grey; border-radius: 1rem;">
                    <h3 style="padding-bottom: 0.5rem; font-weight: 700;">Lote 1</h3>
                    <h5>Escolha</h5>
                    <p style="font-size: 1.35rem;"> <span class="l1_escolha_data_inicio_escola"> </span> à <span class="l1_escolha_data_fim_escola"> </span> </p>
                    <h5>Entrega</h5>
                    <p style="font-size: 1.35rem;"> <span class="l1_entrega_data_escola"> </span> </p>
                </div>
                <div class="w-100 p-3 m-3" style="border: 1px solid grey; border-radius: 1rem;">
                    <h3 style="padding-bottom: 0.5rem; font-weight: 700;">Lote 2</h3>
                    <h5>Escolha</h5>
                    <p style="font-size: 1.35rem;"> <span class="l2_escolha_data_inicio_escola"> </span> à <span class="l2_escolha_data_fim_escola"> </span> </p>
                    <h5>Entrega</h5>
                    <p style="font-size: 1.35rem;"> <span class="l2_entrega_data_escola"> </span> </p>
                </div>
            </div>
        ';
    }
    
    function mostrar_imagem_logo_evento() {
        return '<img class="imagem_logo_evento" src="https://apreas.com.br/wp-content/plugins/elementor/assets/images/placeholder.png" alt="Logo do Evento" style="max-width: 100%; height: auto;">';
    }

    function mostrar_imagem_logo_escola() {
        return '<img class="imagem_logo_escola" src="https://apreas.com.br/wp-content/plugins/elementor/assets/images/placeholder.png" alt="Logo da Escola" style="max-width: 100%; height: auto;">';
    }

    function fotos_counter() {
        ob_start(); 
        ?>
        <div id="selected-count-container" class="text-center" style="font-size: 2rem; font-weight: 400;font-family: 'Roboto', Sans-serif !important;color: grey;">
            <span id="selected-count">0</span>
        </div>
        <?php
        return ob_get_clean(); 
    }

    function shortcode_fotos_container() {
        ob_start(); 
        ?>
        <div id="fotos-container" class="container">
        </div>
        <?php
        return ob_get_clean(); 
    }

    function render_login_form() {
        if ( ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) || ( isset($_GET['action']) && $_GET['action'] === 'elementor' ) ) {
            return '<div style="padding: 20px; background: #f1f1f1; border: 2px dashed #ccc; text-align: center; color: #666; border-radius: 5px;"><strong>Apreas: Formulário de Login</strong><br><em>Ocultado durante a edição para não bloquear a tela.</em></div>';
        }
        ob_start(); ?>
        
        <script>
        (function(){
            try {
                var s = JSON.parse(localStorage.getItem('apreas_login_sessao') || 'null');
                if (s && s.expira && Date.now() < s.expira) {
                    document.write('<style>#loginContainer{display:none!important} #logoutContainer{display:flex!important; z-index: 99999;}</style>');
                }
            } catch(e) {}
        })();
        </script>
        <div id="logoutContainer" style="display:none; position: fixed; bottom: 30px; left: 30px; z-index: 999999; background-color: #fff; padding: 15px 25px; border-radius: 50px; box-shadow: 0 5px 20px rgba(0,0,0,0.25); align-items: center; gap: 15px; border: 1px solid #eee;">
            <span style="font-family: 'Roboto', Sans-serif; font-size: 14px; color: #555; font-weight: 500;">Você está logado.</span>
            <a href="#" id="btnSairApreas" style="color: #d32f2f; font-weight: 700; font-size: 14px; text-decoration: none; font-family: 'Roboto', Sans-serif; display: flex; align-items: center; gap: 5px;">
                Sair <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/></svg>
            </a>
        </div>
        <div id="loginContainer">
            <div id="loginOverlay"></div>
            <div id="loginContent">
                <a type="button" class="btn btn-secondary back" style="" onclick="window.history.back()">
                    <i class="bi bi-arrow-left pe-1" style="font-size: 1.5rem !important; padding-right: 0.7rem !important;"></i> Voltar
                </a>
                <form class="row g-3" id="form_login" name="form_login" enctype="multipart/form-data">
                    <div class="col-md-6">
                        <label for="senha_nome" class="form-label"> Nome e Último Nome </label>
                        <input type="text" class="form-control senha_nome" name="senha_nome" id="senha_nome" placeholder="Nome e Último Nome">
                        <div class="invalid-feedback" id="nameError"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                        <input type="text" class="form-control data_nascimento" name="data_nascimento" id="data_nascimento" placeholder="DD/MM/AAAA" maxlength="10" autocomplete="off">
                        <div class="invalid-feedback" id="dataError"></div>
                    </div>
                    <div class="col-12">
                        <label for="escola" class="form-label">Escola</label>
                        <select class="form-select" name="escola" id="escola" aria-label="Escola">
                        <option value=""> </option>
                        <?php
                            $escolas = get_posts( array(
                                'post_type' => 'escolas',
                                'posts_per_page' => -1, 
                            ) );
                            foreach ( $escolas as $escola ) {
                                echo '<option value="' . esc_attr( $escola->ID ) . '">' . esc_html( $escola->post_title ) . '</option>';
                            }
                        ?>
                        </select>       
                        <div class="invalid-feedback" id="escolaError"></div> 
                    </div>
                    <div class="col-12 duvidas-box">
                        <span class="duvidas-label">Dúvidas e Problemas?</span>
                        <a href="https://wa.me/5511939490911" target="_blank" class="duvidas-whatsapp">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="duvidas-wpp-icon"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            (11) 93949-0911 &nbsp;<em>WhatsApp</em>
                        </a>
                    </div>                     
                    <div class="col-12 mt-4 pt-3 pb-3">
                        <button type="submit" class="btn btn-primary btn_form_login">Acessar</button>
                    </div>
                </form>
            </div>
        </div>
    
        <!-- CSS -->
        <style>
            .back {
                font-family: "Roboto", Sans-serif !important; 
                padding: 25px 0px !important; 
                padding-top: 0 !important;
                border: 0 !important; 
                box-shadow: none !important; 
                background: none !important;
                font-weight: 500 !important;
                text-transform: uppercase !important;
                font-size: 1.2rem !important;
                display: flex;
                align-items: center;
            }
            .back:hover {
                color: black !important;
            }
            #loginContainer {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10;
            }
    
            #loginOverlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgb(0 0 0 / 94%);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                z-index: 1;
            }
    
            #loginContent {
                position: relative;
                z-index: 2;
                background-color: #fff; 
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.5); 
            }
            .duvidas-box{
                padding: 10px 0px 0px !important;
                margin-top: 7px;
                margin-left: 7px;
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .duvidas-label {
                font-size: 0.87rem;
                font-weight: 400;
                color: #999;
                font-family: "Roboto", Sans-serif;
            }
            .duvidas-whatsapp {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                color: #25d366;
                font-weight: 500;
                font-size: 1.1rem;
                font-family: "Roboto", Sans-serif;
                text-decoration: none;
            }
            .duvidas-whatsapp:hover {
                color: #25d366;
                text-decoration: none;
            }
            .duvidas-whatsapp em {
                color: #8d8d8d;
                font-style: inherit;
                font-size: 1rem;
                font-weight: 400;
            }
            .duvidas-wpp-icon {
                width: 15px;
                height: 15px;
                color: #25d366;
                flex-shrink: 0;
            }
            .duvidas-whatsapp:hover .duvidas-wpp-icon {
                color: #25d366;
            }
            form#form_login input:not(.is-invalid, .is-valid) {
                border: 1px solid #c2c2c2 !important;
                border-radius: 5px !important;
            }
            form#form_login select:not(.is-invalid, .is-valid) {
                font-size: 15px !important;
                line-height: 1rem !important;
            }
            .woocommerce-js form#form_login input, .woocommerce-js form#form_login select {
                font-family: "Roboto", Sans-serif !important;
                padding-left: 1rem !important;
            }
 
            .btn_form_login {
                background: #d32f2f !important;
                color: #fff !important;
                font-weight: 500 !important;
                border: 2px solid #d32f2f !important;
                text-transform: uppercase !important;
                margin-top: 8px;
                padding: 8px 17px !important;
                font-family: "Roboto", Sans-serif !important;
                font-size: 1.2rem;
                transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease !important;
            }

            .btn_form_login:focus, .btn_form_login:hover{
                background: #ffffff !important;
                color: #000000 !important;
                border-color: #ffffff !important;
            }

            .woocommerce-js form#form_login input.is-invalid {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-invalid:active {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-invalid:focus {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid:active {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid:focus {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }


            .woocommerce-js form#form_login select.is-invalid {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-invalid:active {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-invalid:focus {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid:active {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid:focus {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
        </style>
        <!-- CSS -->
        
        <?php
        return ob_get_clean();
    }
    
    public function process_login_form() {
        $senha_nome = $_POST['formData']['senha_nome'];
        $senha_nome_transformada = strtolower(str_replace(' ', '_', $senha_nome));
        $data_nascimento = $_POST['formData']['data_nascimento'];
        $data_nascimento_br = '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_nascimento)) {
            $data_nascimento_br = date('d/m/Y', strtotime($data_nascimento));
        }

        $escola = $_POST['formData']['escola'];
    
        $args = array(
            'post_type' => 'alunos',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'senha_nome',
                    'value' => $senha_nome_transformada,
                    'compare' => '='
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'data_nascimento',
                        'value' => $data_nascimento,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'data_nascimento',
                        'value' => $data_nascimento_br,
                        'compare' => '='
                    )
                ),
                array(
                    'key' => 'escola',
                    'value' => $escola,
                    'compare' => '='
                )
            )
        );
    
        $query = new \WP_Query($args);
        foreach ($query->posts as $post) {
            $post_id = $post->ID;
            $imagem_upload_individual = get_post_meta($post_id, 'imagem_upload_individual', true);
            $imagem_upload_individual2 = get_post_meta($post_id, 'imagem_upload_individual2', true);
            $imagem_upload_turma = get_post_meta($post_id, 'imagem_upload_turma', true);

            // [ESCOLA - UNIDADE - TURMA] 
                $escola_id = get_post_meta($post_id, 'escola', true);    
                $unidade_id = get_post_meta($post_id, 'unidade', true);
                $turma_id = get_post_meta($post_id, 'turma', true);
                $escola_post = get_post($escola_id);
                $unidade_post = get_post($unidade_id);
                $turma_post = get_post($turma_id);
            // [ESCOLA - UNIDADE - TURMA] 

            // ESCOLA | CAMPOS EXTRAS
            $imagem_logo_escola = get_post_meta(intval($escola_id), 'imagem_logo_escola', true);
            $l1_escolha_data_inicio_escola = get_post_meta(intval($escola_id), 'l1_escolha_data_inicio', true);
            $l1_escolha_data_fim_escola = get_post_meta(intval($escola_id), 'l1_escolha_data_fim', true);
            $l1_entrega_data_escola = get_post_meta(intval($escola_id), 'l1_entrega_data', true);
            $l2_escolha_data_inicio_escola = get_post_meta(intval($escola_id), 'l2_escolha_data_inicio', true);
            $l2_escolha_data_fim_escola = get_post_meta(intval($escola_id), 'l2_escolha_data_fim', true);
            $l2_entrega_data_escola = get_post_meta(intval($escola_id), 'l2_entrega_data', true);
            $data_limite_fotos_escola = get_post_meta(intval($escola_id), 'data_limite_fotos', true);
            // ESCOLA | CAMPOS EXTRAS

            $dados = [
                'nome' => $post->post_title,
                'imagem_upload_individual' => $imagem_upload_individual,
                'imagem_upload_individual2' => $imagem_upload_individual2,
                'imagem_upload_turma' => $imagem_upload_turma,
                'escola' => [
                    'id' => intval($escola_id),
                    'nome' => $escola_post ? $escola_post->post_title : null,
                    'imagem_logo_escola' => $imagem_logo_escola,
                    'l1_escolha_data_inicio' => $l1_escolha_data_inicio_escola,
                    'l1_escolha_data_fim' => $l1_escolha_data_fim_escola,
                    'l1_entrega_data' => $l1_entrega_data_escola,
                    'l2_escolha_data_inicio' => $l2_escolha_data_inicio_escola,
                    'l2_escolha_data_fim' => $l2_escolha_data_fim_escola,
                    'l2_entrega_data' => $l2_entrega_data_escola,
                    'data_limite_fotos' => $data_limite_fotos_escola
                ],
                'unidade' => [
                    'id' => intval($unidade_id),
                    'nome' => $unidade_post ? $unidade_post->post_title : null
                ],
                'turma' => [
                    'id' => intval($turma_id),
                    'nome' => $turma_post ? $turma_post->post_title : null
                ],
                'data_nascimento' => get_post_meta($post_id, 'data_nascimento', true)
            ];
        }
        if ($query->have_posts()) {
            wp_send_json_success($dados);
        } else {
            wp_send_json_error($dados);
        }
    }

    function render_login_form_eventos($atts) {
        if ( ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) || ( isset($_GET['action']) && $_GET['action'] === 'elementor' ) ) {
            return '<div style="padding: 20px; background: #f1f1f1; border: 2px dashed #ccc; text-align: center; color: #666; border-radius: 5px;"><strong>Apreas: Formulário de Login (Eventos)</strong><br><em>Ocultado durante a edição para não bloquear a tela.</em></div>';
        }
        // Recebendo o parâmetro da categoria do evento no shortcode
        $atts = shortcode_atts(array(
            'categoria' => '',  // Valor padrão
        ), $atts);
    

        ob_start(); ?>
        
        <script>
        (function(){
            try {
                var s = JSON.parse(localStorage.getItem('apreas_login_sessao') || 'null');
                if (s && s.expira && Date.now() < s.expira) {
                    document.write('<style>#loginContainer{display:none!important} #logoutContainer{display:flex!important; z-index: 99999;}</style>');
                }
            } catch(e) {}
        })();
        </script>
        <div id="logoutContainer" style="display:none; position: fixed; bottom: 30px; left: 30px; z-index: 999999; background-color: #fff; padding: 15px 25px; border-radius: 50px; box-shadow: 0 5px 20px rgba(0,0,0,0.25); align-items: center; gap: 15px; border: 1px solid #eee;">
            <span style="font-family: 'Roboto', Sans-serif; font-size: 14px; color: #555; font-weight: 500;">Você está logado.</span>
            <a href="#" id="btnSairApreas" style="color: #d32f2f; font-weight: 700; font-size: 14px; text-decoration: none; font-family: 'Roboto', Sans-serif; display: flex; align-items: center; gap: 5px;">
                Sair <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/></svg>
            </a>
        </div>
        <div id="loginContainer">
            <div id="loginOverlay"></div>
            <div id="loginContent">
                <a type="button" class="btn btn-secondary back" onclick="window.history.back()">
                    <i class="bi bi-arrow-left pe-1" style="font-size: 1.5rem !important; padding-right: 0.7rem !important;"></i> Voltar
                </a>
                <form class="row g-3" id="form_login_eventos" name="form_login_eventos" enctype="multipart/form-data">
                    <div class="col-md-6">
                        <label for="senha_nome" class="form-label"> Nome e Último Nome </label>
                        <input type="text" class="form-control senha_nome" name="senha_nome" id="senha_nome" placeholder="Nome e Último Nome">
                        <div class="invalid-feedback" id="nameError"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="data_nascimento" class="form-label">Data de Nascimento / Data Evento</label>
                        <input type="text" class="form-control data_nascimento" name="data_nascimento" id="data_nascimento" placeholder="DD/MM/AAAA" maxlength="10" autocomplete="off">
                        <div class="invalid-feedback" id="dataError"></div>
                    </div>
                    <div class="col-12">
                        <label for="evento" class="form-label">Evento</label>
                        <select class="form-select" name="evento" id="evento" aria-label="Evento">
                        <option value=""> </option>
                        <?php

                            // Query para buscar os eventos
                            $args = array(
                                'post_type'             => 'eventos',
                                'posts_per_page'        => -1,
                                'post_status'           => 'publish',
                            );

                            $eventos = get_posts($args);

                            foreach ($eventos as $evento) {
                                $categorias_eventos = get_post_field( 'categorias_eventos', $evento->ID );
                                foreach ($categorias_eventos as $c) {
                                    if($c == $atts['categoria']){
                                        echo '<option value="' . esc_attr($evento->ID) . '">' . esc_html($evento->post_title) . '</option>';
                                    }
                                }
                            }
                        ?>
                        </select>       
                        <div class="invalid-feedback" id="eventoError"></div>     
                    </div>
                    <div class="col-12 duvidas-box">
                        <span class="duvidas-label">Dúvidas e Problemas?</span>
                        <a href="https://wa.me/5511939490911" target="_blank" class="duvidas-whatsapp">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="duvidas-wpp-icon"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            (11) 93949-0911 &nbsp;<em>WhatsApp</em>
                        </a>
                    </div>                      
                    <div class="col-12 mt-4 pt-3 pb-3">
                        <button type="submit" class="btn btn-primary btn_form_login">Acessar</button>
                    </div>
                </form>
            </div>
        </div>
    
        <style>
            .back {
                font-family: "Roboto", Sans-serif !important; 
                padding: 25px 0px !important; 
                padding-top: 0 !important;
                border: 0 !important; 
                box-shadow: none !important; 
                background: none !important;
                font-weight: 500 !important;
                text-transform: uppercase !important;
                font-size: 1.2rem !important;
                display: flex;
                align-items: center;
            }
            .back:hover {
                color: black !important;
            }
            #loginContainer {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10;
            }
    
            #loginOverlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgb(0 0 0 / 97%);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                z-index: 1;
            }
    
            #loginContent {
                position: relative;
                z-index: 2;
                background-color: #fff; 
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.5); 
            }
            .duvidas-box {
                padding: 2px 0 !important;
                margin-top: 2px;
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .duvidas-label {
                font-size: 0.72rem;
                font-weight: 400;
                color: #999;
                font-family: "Roboto", Sans-serif;
            }
            .duvidas-whatsapp {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                color: #25d366;
                font-weight: 500;
                font-size: 0.88rem;
                font-family: "Roboto", Sans-serif;
                text-decoration: none;
            }
            .duvidas-whatsapp:hover {
                color: #25d366;
                text-decoration: none;
            }
            .duvidas-whatsapp em {
                color: #000;
                font-style: normal;
            }
            .duvidas-wpp-icon {
                width: 15px;
                height: 15px;
                color: #25d366;
                flex-shrink: 0;
            }
            .duvidas-whatsapp:hover .duvidas-wpp-icon {
                color: #25d366;
            }
            form#form_login input:not(.is-invalid, .is-valid) {
                border: 1px solid #c2c2c2 !important;
                border-radius: 5px !important;
            }
            form#form_login select:not(.is-invalid, .is-valid) {
                font-size: 15px !important;
                line-height: 1rem !important;
            }
            .woocommerce-js form#form_login input, .woocommerce-js form#form_login select {
                font-family: "Roboto", Sans-serif !important;
                padding-left: 1rem !important;
            }
 
            .btn_form_login {
                background: #d32f2f !important;
                color: #fff !important;
                font-weight: 500 !important;
                border: 2px solid #d32f2f !important;
                text-transform: uppercase !important;
                margin-top: 8px;
                padding: 8px 17px !important;
                font-family: "Roboto", Sans-serif !important;
                font-size: 1.2rem;
                transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease !important;
            }

            .btn_form_login:focus, .btn_form_login:hover{
                background: #ffffff !important;
                color: #000000 !important;
                border-color: #ffffff !important;
            }

            .woocommerce-js form#form_login input.is-invalid {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-invalid:active {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-invalid:focus {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid:active {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login input.is-valid:focus {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }


            .woocommerce-js form#form_login select.is-invalid {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-invalid:active {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-invalid:focus {
                border: 1px solid rgb(220, 53, 69);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid:active {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }
            .woocommerce-js form#form_login select.is-valid:focus {
                border: 1px solid rgb(25, 135, 84);
                border-radius: 5px !important;
            }

        </style>
        
        <?php
        return ob_get_clean();
    }
    
    // public function process_login_form_eventos() {
    //     $senha_nome = $_POST['formData']['senha_nome'];
    //     $senha_nome_transformada = strtolower(str_replace(' ', '_', $senha_nome));
    //     $data_nascimento = $_POST['formData']['data_nascimento'];
    //     $evento = $_POST['formData']['evento'];
    
    //     $args = array(
    //         'post_type' => 'participantes',
    //         'meta_query' => array(
    //             'relation' => 'AND',
    //             array(
    //                 'key' => 'senha_nome',
    //                 'value' => $senha_nome_transformada,
    //                 'compare' => '='
    //             ),
    //             array(
    //                 'key' => 'data_nascimento',
    //                 'value' => $data_nascimento,
    //                 'compare' => '='
    //             ),
    //             array(
    //                 'key' => 'evento',
    //                 'value' => $evento,
    //                 'compare' => '='
    //             )
    //         )
    //     );
    
    //     $query = new \WP_Query($args);
    //     foreach ($query->posts as $post) {
    //         $post_id = $post->ID;
    //         $fotos = get_post_meta($post_id, '_fotos_participantes', true) ?: array(); // Garantir que $fotos seja um array
    //         $dados = [
    //             'fotos_participante' => $fotos
    //         ];
    //     }
    //     if ($query->have_posts()) {
    //         wp_send_json_success($dados);
    //     } else {
    //         wp_send_json_error($dados);
    //     }
    // }

    public function process_login_form_eventos() {
        $senha_nome = $_POST['formData']['senha_nome'];
        $senha_nome_transformada = strtolower(str_replace(' ', '_', $senha_nome));
        $data_nascimento = $_POST['formData']['data_nascimento'];
        $data_nascimento_br = '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_nascimento)) {
            $data_nascimento_br = date('d/m/Y', strtotime($data_nascimento));
        }

        $evento = $_POST['formData']['evento'];
    
        
        $args = array(
            'post_type' => 'participantes',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'senha_nome',
                    'value' => $senha_nome_transformada,
                    'compare' => '='
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'data_nascimento',
                        'value' => $data_nascimento,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'data_nascimento',
                        'value' => $data_nascimento_br,
                        'compare' => '='
                    )
                ),
                array(
                    'key' => 'evento',
                    'value' => $evento,
                    'compare' => '='
                )
            )
        );
    
        $query = new \WP_Query($args);
        $dados = array(); // Inicializar array de dados
    
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $post_id = $post->ID;
                $fotos = get_post_meta($post_id, '_fotos_participantes', true) ?: array(); // Garantir que $fotos seja um array
                
                $imagem_upload_individual = get_post_meta($post_id, 'imagem_upload_individual', true);
                $imagem_upload_individual2 = get_post_meta($post_id, 'imagem_upload_individual2', true);
                $imagem_upload_turma = get_post_meta($post_id, 'imagem_upload_turma', true);
                $link_album = get_post_meta($post_id, 'link_album', true);

                // [ESCOLA - UNIDADE - TURMA] 
                    $evento_id = get_post_meta($post_id, 'evento', true);    
                    $escola_id = get_post_meta($post_id, 'escola', true);    
                    $unidade_id = get_post_meta($post_id, 'unidade', true);
                    $turma_id = get_post_meta($post_id, 'turma', true);
                    $evento_post = get_post($evento_id);
                    $escola_post = get_post($escola_id);
                    $unidade_post = get_post($unidade_id);
                    $turma_post = get_post($turma_id);
                // [ESCOLA - UNIDADE - TURMA] 

                // Montar as fotos com nome, caminho e código
                $fotos_formatadas = array();
                foreach ($fotos as $foto) {
                    $fotos_formatadas[] = array(
                        'nome' => $foto['nome'],
                        'caminho' => $foto['caminho'],
                        'codigo' => $foto['codigo']
                    );
                }

                // ESCOLA | CAMPOS EXTRAS
                $imagem_logo_escola = get_post_meta(intval($escola_id), 'imagem_logo_escola', true);
                $l1_escolha_data_inicio_escola = get_post_meta(intval($escola_id), 'l1_escolha_data_inicio', true);
                $l1_escolha_data_fim_escola = get_post_meta(intval($escola_id), 'l1_escolha_data_fim', true);
                $l1_entrega_data_escola = get_post_meta(intval($escola_id), 'l1_entrega_data', true);
                $l2_escolha_data_inicio_escola = get_post_meta(intval($escola_id), 'l2_escolha_data_inicio', true);
                $l2_escolha_data_fim_escola = get_post_meta(intval($escola_id), 'l2_escolha_data_fim', true);
                $l2_entrega_data_escola = get_post_meta(intval($escola_id), 'l2_entrega_data', true);
                // ESCOLA | CAMPOS EXTRAS

                // EVENTO | CAMPOS EXTRAS
                $imagem_logo_evento = get_post_meta(intval($evento_id), 'imagem_logo_evento', true);
                $l1_escolha_data_inicio_evento = get_post_meta(intval($evento_id), 'l1_escolha_data_inicio', true);
                $l1_escolha_data_fim_evento = get_post_meta(intval($evento_id), 'l1_escolha_data_fim', true);
                $l1_entrega_data_evento = get_post_meta(intval($evento_id), 'l1_entrega_data', true);
                $l2_escolha_data_inicio_evento = get_post_meta(intval($evento_id), 'l2_escolha_data_inicio', true);
                $l2_escolha_data_fim_evento = get_post_meta(intval($evento_id), 'l2_escolha_data_fim', true);
                $l2_entrega_data_evento = get_post_meta(intval($evento_id), 'l2_entrega_data', true);
                // EVENTO | CAMPOS EXTRAS
    
                $dados[] = [
                    'nome' => $post->post_title,
                    'fotos_participante' => $fotos_formatadas,
                    'imagem_upload_individual' => $imagem_upload_individual,
                    'imagem_upload_individual2' => $imagem_upload_individual2,
                    'imagem_upload_turma' => $imagem_upload_turma,
                    'link_album' => $link_album,
                    'evento' => [
                        'id' => intval($evento_id),
                        'nome' => $evento_post ? $evento_post->post_title : null,
                        'imagem_logo_evento' => $imagem_logo_evento,
                        'l1_escolha_data_inicio' => $l1_escolha_data_inicio_evento,
                        'l1_escolha_data_fim' => $l1_escolha_data_fim_evento,
                        'l1_entrega_data' => $l1_entrega_data_evento,
                        'l2_escolha_data_inicio' => $l2_escolha_data_inicio_evento,
                        'l2_escolha_data_fim' => $l2_escolha_data_fim_evento,
                        'l2_entrega_data' => $l2_entrega_data_evento
                    ],
                    'escola' => [
                        'id' => intval($escola_id),
                        'nome' => $escola_post ? $escola_post->post_title : null,
                        'imagem_logo_escola' => $imagem_logo_escola,
                        'l1_escolha_data_inicio' => $l1_escolha_data_inicio_escola,
                        'l1_escolha_data_fim' => $l1_escolha_data_fim_escola,
                        'l1_entrega_data' => $l1_entrega_data_escola,
                        'l2_escolha_data_inicio' => $l2_escolha_data_inicio_escola,
                        'l2_escolha_data_fim' => $l2_escolha_data_fim_escola,
                        'l2_entrega_data' => $l2_entrega_data_escola
                    ],
                    'unidade' => [
                        'id' => intval($unidade_id),
                        'nome' => $unidade_post ? $unidade_post->post_title : null
                    ],
                    'turma' => [
                        'id' => intval($turma_id),
                        'nome' => $turma_post ? $turma_post->post_title : null
                    ],
                    'data_nascimento' => get_post_meta($post_id, 'data_nascimento', true)
                ];
            }
            wp_send_json_success($dados);
        } else {
            wp_send_json_error(['message' => 'Participante não encontrado.']);
        }
    }
    
}

Login::getInstance();
