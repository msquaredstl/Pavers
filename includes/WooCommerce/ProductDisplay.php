<?php

declare(strict_types=1);

namespace Pavers\WooCommerce;

class ProductDisplay
{
    private PaverProduct $paverProduct;

    public function __construct(PaverProduct $paverProduct)
    {
        $this->paverProduct = $paverProduct;
    }

    public function register(): void
    {
        add_action('woocommerce_after_add_to_cart_form', [$this, 'maybeRenderForm']);
    }

    public function maybeRenderForm(): void
    {
        if (! function_exists('is_product') || ! is_product()) {
            return;
        }

        global $product;

        if (! $product) {
            return;
        }

        $productId = (int) $product->get_id();

        if (! $this->paverProduct->isPaverProduct($productId)) {
            return;
        }

        echo do_shortcode('[paver_order_form product_id="' . esc_attr((string) $productId) . '"]');
    }
}
