<?php

declare(strict_types=1);

namespace Pavers\WooCommerce;

class PaverProduct
{
    private const META_KEY = '_pavers_enable';

    public function register(): void
    {
        add_filter('woocommerce_product_data_tabs', [$this, 'addProductTab']);
        add_action('woocommerce_product_data_panels', [$this, 'renderProductTab']);
        add_action('woocommerce_process_product_meta', [$this, 'saveProductMeta']);
    }

    public function addProductTab(array $tabs): array
    {
        $tabs['pavers'] = [
            'label' => __('Paver Settings', 'pavers'),
            'target' => 'pavers_product_data',
            'class' => [],
        ];

        return $tabs;
    }

    public function renderProductTab(): void
    {
        global $post;

        ?>
        <div id="pavers_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                woocommerce_wp_checkbox([
                    'id' => self::META_KEY,
                    'label' => __('Enable Paver Customization', 'pavers'),
                    'description' => __('Show engraving layout fields on this product page and save them with the order.', 'pavers'),
                    'value' => get_post_meta($post->ID, self::META_KEY, true),
                ]);
                ?>
            </div>
        </div>
        <?php
    }

    public function saveProductMeta(int $productId): void
    {
        $enabled = isset($_POST[self::META_KEY]) ? 'yes' : '';
        update_post_meta($productId, self::META_KEY, $enabled);
    }

    public function isPaverProduct(int $productId): bool
    {
        return get_post_meta($productId, self::META_KEY, true) === 'yes';
    }
}
