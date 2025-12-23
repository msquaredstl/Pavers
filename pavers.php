<?php
/**
 * Plugin Name: Pavers
 * Description: Handle paver purchase requests and display IAFF 2665 retiree pavers.
 * Version: 1.0.0
 * Author: IAFF 2665 Retirees
 * License: GPL-2.0-or-later
 * Text Domain: pavers
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('PAVERS_PLUGIN_FILE', __FILE__);
define('PAVERS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PAVERS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once PAVERS_PLUGIN_PATH . 'includes/Autoloader.php';

Pavers\Autoloader::register();

$paversPlugin = new Pavers\Plugin();

register_activation_hook(PAVERS_PLUGIN_FILE, static function () use ($paversPlugin) {
    $paversPlugin->activate();
});

add_action('plugins_loaded', static function () use ($paversPlugin) {
    $paversPlugin->register();
});
