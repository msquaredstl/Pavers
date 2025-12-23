<?php

declare(strict_types=1);

namespace Pavers;

use Pavers\Admin\SettingsPage;
use Pavers\Assets\Enqueue;
use Pavers\Frontend\FormHandler;
use Pavers\Frontend\Shortcodes;
use Pavers\PostTypes\PaverOrder;
use Pavers\WooCommerce\PaverProduct;
use Pavers\WooCommerce\ProductDisplay;

class Plugin
{
    private SettingsPage $settingsPage;
    private PaverOrder $paverOrder;
    private Shortcodes $shortcodes;
    private FormHandler $formHandler;
    private Enqueue $enqueue;
    private ?PaverProduct $paverProduct = null;
    private ?ProductDisplay $productDisplay = null;

    public function __construct()
    {
        $this->paverOrder = new PaverOrder();
        $this->settingsPage = new SettingsPage();
        $this->shortcodes = new Shortcodes($this);
        $this->formHandler = new FormHandler($this);
        $this->enqueue = new Enqueue();
        $this->bootWooCommerce();
    }

    public function register(): void
    {
        add_action('init', [$this, 'loadTextDomain']);
        add_action('init', [$this->paverOrder, 'register']);
        add_action('init', [$this->formHandler, 'register']);
        add_action('init', [$this->shortcodes, 'register']);
        add_action('init', [$this->enqueue, 'register']);
        add_action('admin_init', [$this->settingsPage, 'register']);
        add_action('admin_menu', [$this->settingsPage, 'addMenu']);

        if ($this->paverProduct && $this->productDisplay) {
            $this->paverProduct->register();
            $this->productDisplay->register();
        }
    }

    public function activate(): void
    {
        $this->paverOrder->register();
        flush_rewrite_rules();
    }

    public function getOption(string $key, $default = '')
    {
        $options = get_option(SettingsPage::OPTION_KEY, SettingsPage::defaults());
        $defaults = SettingsPage::defaults();

        return $options[$key] ?? ($defaults[$key] ?? $default);
    }

    public function isPaverProduct(int $productId): bool
    {
        if (! $this->paverProduct) {
            return false;
        }

        return $this->paverProduct->isPaverProduct($productId);
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain('pavers', false, dirname(plugin_basename(PAVERS_PLUGIN_FILE)) . '/languages');
    }

    private function bootWooCommerce(): void
    {
        if (! class_exists('\\WooCommerce')) {
            return;
        }

        $this->paverProduct = new PaverProduct();
        $this->productDisplay = new ProductDisplay($this->paverProduct);
    }
}
