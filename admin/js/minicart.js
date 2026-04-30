/**
 * Apreas Mini Carrinho Flutuante
 * Versão: 1.0.0
 */
(function ($) {
    'use strict';

    /* ─────────────────────────────────────────────
       Referências DOM
    ───────────────────────────────────────────── */
    var $trigger, $panel, $overlay, $badge, $itemsList, $subtotalVal, $emptyMsg, $itemsWrap, $footerWrap;

    function init() {
        $trigger    = $('#apreas-minicart-trigger');
        $panel      = $('#apreas-minicart-panel');
        $overlay    = $('#apreas-minicart-overlay');
        $badge      = $('#apreas-minicart-badge');
        $itemsList  = $('#apreas-minicart-items');
        $subtotalVal= $('#apreas-minicart-subtotal-value');
        var $feeRow = $('#apreas-minicart-fee-row');
        var $feeVal = $('#apreas-minicart-fee-value');
        var $totalVal= $('#apreas-minicart-total-value');
        $emptyMsg   = $('#apreas-minicart-empty');
        $itemsWrap  = $('#apreas-minicart-items-wrap');
        $footerWrap = $('#apreas-minicart-items-wrap-footer');

        // Store these for renderCart if not global
        window.$apreas_fee_row = $feeRow;
        window.$apreas_fee_val = $feeVal;
        window.$apreas_total_val = $totalVal;

        bindEvents();
        updateFromFragment();
    }

    /* ─────────────────────────────────────────────
       Eventos
    ───────────────────────────────────────────── */
    function bindEvents() {

        // Abrir / fechar painel
        $trigger.on('click', function () {
            togglePanel();
        });

        // Fechar ao clicar no overlay
        $overlay.on('click', function () {
            closePanel();
        });

        // Fechar com ESC
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closePanel();
        });

        // Remover item (delegação — os botões são criados dinamicamente)
        $(document).on('click', '.apreas-minicart-remove', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var key  = $btn.data('cart-item-key');
            removeItem(key, $btn);
        });

        /* Atualiza quando WooCommerce atualiza os fragments
           (add-to-cart, qty, etc.) */
        $(document.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart removed_from_cart', function () {
            updateFromFragment();
            flashBadge();
        });
    }

    /* ─────────────────────────────────────────────
       Painel
    ───────────────────────────────────────────── */
    function openPanel() {
        $panel.addClass('apreas-minicart--open');
        $overlay.addClass('apreas-minicart-overlay--visible');
        $trigger.attr('aria-expanded', 'true');
    }

    function closePanel() {
        $panel.removeClass('apreas-minicart--open');
        $overlay.removeClass('apreas-minicart-overlay--visible');
        $trigger.attr('aria-expanded', 'false');
    }

    function togglePanel() {
        if ($panel.hasClass('apreas-minicart--open')) {
            closePanel();
        } else {
            openPanel();
            updateFromFragment(); // força atualização ao abrir
        }
    }

    /* ─────────────────────────────────────────────
       Atualização via AJAX (WooCommerce fragments)
    ───────────────────────────────────────────── */
    function updateFromFragment() {
        if (typeof apreas_minicart === 'undefined') return;

        $.ajax({
            url: apreas_minicart.ajax_url,
            method: 'POST',
            data: {
                action: 'apreas_get_minicart_data',
                nonce:  apreas_minicart.nonce
            },
            success: function (response) {
                if (response.success) {
                    renderCart(response.data);
                }
            }
        });
    }

    function renderCart(data) {
        var count    = parseInt(data.count) || 0;
        var subtotal = data.subtotal || '';
        var items    = data.items   || [];

        // Badge
        $badge.text(count);
        if (count > 0) {
            $badge.addClass('apreas-minicart-badge--visible');
        } else {
            $badge.removeClass('apreas-minicart-badge--visible');
        }

        // Lista
        $itemsList.empty();

        if (items.length === 0) {
            $emptyMsg.show();
            $itemsWrap.hide();
            $footerWrap.hide();
        } else {
            $emptyMsg.hide();
            $itemsWrap.show();
            $footerWrap.show();

            $.each(items, function (i, item) {
                var $li = $(
                    '<li class="apreas-minicart-item">' +
                        '<div class="apreas-minicart-item__thumb">' +
                            (item.thumb ? '<img src="' + item.thumb + '" alt="' + escHtml(item.name) + '">' : '<span class="apreas-minicart-item__no-img">📦</span>') +
                        '</div>' +
                        '<div class="apreas-minicart-item__info">' +
                            '<span class="apreas-minicart-item__name">' + escHtml(item.name) + '</span>' +
                            '<span class="apreas-minicart-item__meta">' + item.qty + '× ' + item.price + '</span>' +
                        '</div>' +
                        '<button class="apreas-minicart-remove" data-cart-item-key="' + item.key + '" title="Remover" aria-label="Remover ' + escHtml(item.name) + '">' +
                            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                        '</button>' +
                    '</li>'
                );
                $itemsList.append($li);
            });

            $subtotalVal.html(subtotal);

            // Fee
            if (data.fee_raw && parseFloat(data.fee_raw) > 0) {
                window.$apreas_fee_val.html(data.fee);
                window.$apreas_fee_row.css('display', 'flex');
            } else {
                window.$apreas_fee_row.hide();
            }

            // Total
            window.$apreas_total_val.html(data.total);
        }
    }

    /* ─────────────────────────────────────────────
       Remover item
    ───────────────────────────────────────────── */
    function removeItem(key, $btn) {
        if (typeof apreas_minicart === 'undefined') return;

        $btn.prop('disabled', true).css('opacity', '0.5');

        $.ajax({
            url: apreas_minicart.ajax_url,
            method: 'POST',
            data: {
                action: 'apreas_remove_minicart_item',
                nonce:  apreas_minicart.nonce,
                key:    key
            },
            success: function (response) {
                if (response.success) {
                    updateFromFragment();
                    $(document.body).trigger('wc_fragment_refresh');
                }
            },
            error: function () {
                $btn.prop('disabled', false).css('opacity', '1');
            }
        });
    }

    /* ─────────────────────────────────────────────
       Animação do badge
    ───────────────────────────────────────────── */
    function flashBadge() {
        $badge.addClass('apreas-minicart-badge--pulse');
        setTimeout(function () {
            $badge.removeClass('apreas-minicart-badge--pulse');
        }, 600);
    }

    /* ─────────────────────────────────────────────
       Util
    ───────────────────────────────────────────── */
    function escHtml(str) {
        return $('<div>').text(str).html();
    }

    /* ─────────────────────────────────────────────
       Bootstrap
    ───────────────────────────────────────────── */
    $(document).ready(function () {
        if ($('#apreas-minicart-trigger').length) {
            init();
        }
    });

})(jQuery);
