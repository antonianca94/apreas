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
    }

    public function add_settings_page()
    {
        add_menu_page(
            'Configurações Apreas',
            'Apreas',
            'manage_options',
            'apreas-settings',
            [$this, 'render_settings_page'],
            'dashicons-admin-generic',
            30
        );
    }

    public function register_settings()
    {
        register_setting('apreas_settings_group', 'apreas_minicart_enabled');
        register_setting('apreas_settings_group', 'apreas_taxa_fixa_enabled');
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
                        <h2>Módulo de Taxas</h2>
                    </div>
                    
                    <div class="apreas-setting-row">
                        <div class="apreas-setting-info">
                            <strong>Taxa de Entrega Fixa</strong>
                            <p>Adiciona uma taxa fixa de R$ 20,00 no checkout para todos os pedidos.</p>
                        </div>
                        <div class="apreas-setting-control">
                            <label class="apreas-switch">
                                <input type="hidden" name="apreas_taxa_fixa_enabled" value="0">
                                <input type="checkbox" name="apreas_taxa_fixa_enabled" value="1" <?php checked(1, get_option('apreas_taxa_fixa_enabled', 0)); ?>>
                                <span class="apreas-slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>

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
        </style>
        <?php
    }
}
