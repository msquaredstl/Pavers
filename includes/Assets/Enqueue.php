<?php

declare(strict_types=1);

namespace Pavers\Assets;

class Enqueue
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        wp_enqueue_style(
            'pavers-styles',
            PAVERS_PLUGIN_URL . 'assets/css/pavers.css',
            [],
            '1.0.0'
        );
    }
}
