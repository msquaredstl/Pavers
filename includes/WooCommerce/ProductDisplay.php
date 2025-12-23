<?php

declare(strict_types=1);

namespace Pavers\WooCommerce;

class ProductDisplay
{
    private const FIELD_NONCE = 'pavers_customization_nonce';
    private const FIELD_LINE1 = 'pavers_line1';
    private const FIELD_LINE2 = 'pavers_line2';
    private const FIELD_LINE3 = 'pavers_line3';
    private const FIELD_ALIGNMENT = 'pavers_alignment';

    private PaverProduct $paverProduct;

    public function __construct(PaverProduct $paverProduct)
    {
        $this->paverProduct = $paverProduct;
    }

    public function register(): void
    {
        add_action('woocommerce_before_add_to_cart_button', [$this, 'renderCustomizer']);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate'], 10, 3);
        add_filter('woocommerce_add_cart_item_data', [$this, 'addCartItemData'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'renderCartItemData'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'addOrderItemMeta'], 10, 4);
    }

    public function renderCustomizer(): void
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

        $data = $this->getRequestValues();

        include PAVERS_PLUGIN_PATH . 'templates/paver-customizer.php';
    }

    public function validate(bool $passed, int $productId, int $quantity): bool
    {
        if (! $this->paverProduct->isPaverProduct($productId)) {
            return $passed;
        }

        if (! isset($_POST[self::FIELD_NONCE]) || ! wp_verify_nonce((string) $_POST[self::FIELD_NONCE], 'pavers_customize')) {
            wc_add_notice(__('Please customize your paver before adding it to the cart.', 'pavers'), 'error');

            return false;
        }

        $customization = $this->getRequestValues();

        if ($customization['line1'] === '') {
            wc_add_notice(__('Line 1 is required for the paver engraving.', 'pavers'), 'error');

            return false;
        }

        return $passed;
    }

    public function addCartItemData(array $cartItemData, int $productId, int $variationId): array
    {
        if (! $this->paverProduct->isPaverProduct($productId)) {
            return $cartItemData;
        }

        $customization = $this->getRequestValues();

        if ($customization['line1'] === '') {
            return $cartItemData;
        }

        $cartItemData['pavers_customization'] = $customization;
        $cartItemData['unique_key'] = md5(wp_json_encode($customization) . microtime(true));

        return $cartItemData;
    }

    public function renderCartItemData(array $itemData, array $cartItem): array
    {
        if (! isset($cartItem['pavers_customization'])) {
            return $itemData;
        }

        $customization = $cartItem['pavers_customization'];

        $itemData[] = [
            'name' => __('Paver Layout', 'pavers'),
            'value' => $this->formatLayoutSummary($customization),
            'display' => '',
        ];

        $itemData[] = [
            'name' => __('Alignment', 'pavers'),
            'value' => ucfirst($customization['alignment']),
            'display' => '',
        ];

        return $itemData;
    }

    public function addOrderItemMeta($item, $cartItemKey, $values, $order): void
    {
        if (! isset($values['pavers_customization'])) {
            return;
        }

        $customization = $values['pavers_customization'];

        $item->add_meta_data(__('Paver Line 1', 'pavers'), $customization['line1'], true);

        if ($customization['line2'] !== '') {
            $item->add_meta_data(__('Paver Line 2', 'pavers'), $customization['line2'], true);
        }

        if ($customization['line3'] !== '') {
            $item->add_meta_data(__('Paver Line 3', 'pavers'), $customization['line3'], true);
        }

        $item->add_meta_data(__('Alignment', 'pavers'), ucfirst($customization['alignment']), true);
    }

    private function getRequestValues(): array
    {
        $line1 = isset($_POST[self::FIELD_LINE1]) ? wc_clean(wp_unslash($_POST[self::FIELD_LINE1])) : '';
        $line2 = isset($_POST[self::FIELD_LINE2]) ? wc_clean(wp_unslash($_POST[self::FIELD_LINE2])) : '';
        $line3 = isset($_POST[self::FIELD_LINE3]) ? wc_clean(wp_unslash($_POST[self::FIELD_LINE3])) : '';
        $alignment = isset($_POST[self::FIELD_ALIGNMENT]) ? strtolower(wc_clean(wp_unslash($_POST[self::FIELD_ALIGNMENT]))) : 'center';

        if (! in_array($alignment, ['left', 'center', 'right'], true)) {
            $alignment = 'center';
        }

        return [
            'line1' => $line1,
            'line2' => $line2,
            'line3' => $line3,
            'alignment' => $alignment,
        ];
    }

    private function formatLayoutSummary(array $customization): string
    {
        $lines = array_filter([
            $customization['line1'],
            $customization['line2'],
            $customization['line3'],
        ], static function ($line) {
            return $line !== '';
        });

        return implode("\n", $lines);
    }
}
