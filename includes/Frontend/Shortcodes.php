<?php

declare(strict_types=1);

namespace Pavers\Frontend;

use Pavers\Plugin;

class Shortcodes
{
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function register(): void
    {
        add_shortcode('paver_order_form', [$this, 'renderForm']);
        add_shortcode('paver_wall', [$this, 'renderWall']);
    }

    public function renderForm(array $attributes = [], string $content = ''): string
    {
        $productId = $this->determineProductId($attributes);

        if ($productId === 0 || ! $this->plugin->isPaverProduct($productId)) {
            return '';
        }

        ob_start();
        $data = [
            'action' => admin_url('admin-post.php'),
            'success_message' => $this->plugin->getOption('success_message'),
            'error_message' => $this->plugin->getOption('error_message'),
            'disclaimer' => $this->plugin->getOption('disclaimer'),
            'status' => isset($_GET['pavers_status']) ? sanitize_text_field((string) $_GET['pavers_status']) : '',
            'product_id' => $productId,
        ];

        include PAVERS_PLUGIN_PATH . 'templates/order-form.php';

        return (string) ob_get_clean();
    }

    public function renderWall(array $attributes = []): string
    {
        $count = isset($attributes['count']) ? absint($attributes['count']) : 12;
        $orders = get_posts([
            'post_type' => 'paver_order',
            'post_status' => ['publish', 'pending'],
            'numberposts' => $count,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();
        include PAVERS_PLUGIN_PATH . 'templates/paver-wall.php';

        return (string) ob_get_clean();
    }

    private function determineProductId(array $attributes): int
    {
        if (isset($attributes['product_id'])) {
            return absint($attributes['product_id']);
        }

        if (function_exists('is_product') && is_product()) {
            global $product;

            if ($product && method_exists($product, 'get_id')) {
                return (int) $product->get_id();
            }
        }

        return 0;
    }
}
