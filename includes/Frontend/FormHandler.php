<?php

declare(strict_types=1);

namespace Pavers\Frontend;

use Pavers\Admin\SettingsPage;
use Pavers\Plugin;
use Pavers\PostTypes\PaverOrder;

class FormHandler
{
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function register(): void
    {
        add_action('admin_post_nopriv_pavers_submit_order', [$this, 'handleSubmission']);
        add_action('admin_post_pavers_submit_order', [$this, 'handleSubmission']);
    }

    public function handleSubmission(): void
    {
        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce((string) $_POST['_wpnonce'], 'pavers_submit_order')) {
            wp_safe_redirect($this->redirectUrl('error'));
            exit;
        }

        $productId = absint($_POST['pavers_product_id'] ?? 0);
        $name = sanitize_text_field(wp_unslash($_POST['pavers_name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['pavers_email'] ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['pavers_phone'] ?? ''));
        $inscription = sanitize_textarea_field(wp_unslash($_POST['pavers_inscription'] ?? ''));

        if ($productId === 0 || ! $this->plugin->isPaverProduct($productId)) {
            wp_safe_redirect($this->redirectUrl('error'));
            exit;
        }

        if ($name === '' || $email === '' || $inscription === '') {
            wp_safe_redirect($this->redirectUrl('error'));
            exit;
        }

        $postId = wp_insert_post([
            'post_type' => PaverOrder::POST_TYPE,
            'post_status' => 'pending',
            'post_title' => sprintf(__('Paver request from %s', 'pavers'), $name),
        ]);

        if (! $postId || is_wp_error($postId)) {
            wp_safe_redirect($this->redirectUrl('error'));
            exit;
        }

        update_post_meta($postId, 'pavers_donor_name', $name);
        update_post_meta($postId, 'pavers_donor_email', $email);
        update_post_meta($postId, 'pavers_donor_phone', $phone);
        update_post_meta($postId, 'pavers_inscription', $inscription);
        update_post_meta($postId, 'pavers_product_id', $productId);

        $this->notifyAdmin($name, $email, $phone, $inscription, (int) $postId, $productId);

        wp_safe_redirect($this->redirectUrl('success'));
        exit;
    }

    private function notifyAdmin(string $name, string $email, string $phone, string $inscription, int $postId, int $productId): void
    {
        $recipient = $this->plugin->getOption('recipient_email', get_option('admin_email'));
        $subject = __('New Paver Request', 'pavers');
        $productTitle = get_the_title($productId) ?: __('Paver', 'pavers');

        $message = sprintf(
            "%s\n\n%s: %s\n%s: %s\n%s: %s\n%s: %s\n\n%s\n%s",
            __('A new paver request has been submitted.', 'pavers'),
            __('Name', 'pavers'),
            $name,
            __('Email', 'pavers'),
            $email,
            __('Phone', 'pavers'),
            $phone,
            __('Product', 'pavers'),
            $productTitle,
            __('Inscription', 'pavers'),
            $inscription
        );

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
        ];

        if ($email) {
            $headers[] = 'Reply-To: ' . $email;
        }

        wp_mail($recipient, $subject, $message, $headers);
    }

    private function redirectUrl(string $status): string
    {
        $referer = wp_get_referer() ?: home_url('/');

        return add_query_arg('pavers_status', $status, $referer);
    }
}
