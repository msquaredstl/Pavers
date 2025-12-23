<?php

declare(strict_types=1);

namespace Pavers\WooCommerce;

class PaverProduct
{
    private const META_KEY = '_pavers_enable';

    public function register(): void
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'renderProductField']);
        add_action('woocommerce_process_product_meta', [$this, 'saveProductMeta']);
    }

    public function renderProductField(): void
    {
        global $post;

        ?>
        <div class="options_group">
            <?php
            woocommerce_wp_checkbox([
                'id' => self::META_KEY,
                'label' => __('Enable Paver Customization', 'pavers'),
                'description' => __('Allow customers to submit engraving layout details when adding this product to the cart.', 'pavers'),
                'value' => get_post_meta($post->ID, self::META_KEY, true),
            ]);
            ?>
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
