<?php

declare(strict_types=1);

namespace Pavers\PostTypes;

class PaverOrder
{
    public const POST_TYPE = 'paver_order';

    public function register(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Paver Orders', 'pavers'),
                'singular_name' => __('Paver Order', 'pavers'),
                'menu_name' => __('Paver Orders', 'pavers'),
                'add_new_item' => __('Add New Paver Order', 'pavers'),
                'edit_item' => __('Edit Paver Order', 'pavers'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title'],
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-hammer',
        ]);

        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'saveMeta'], 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'registerColumns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'renderColumns'], 10, 2);
    }

    public function addMetaBoxes(): void
    {
        add_meta_box(
            'pavers_order_details',
            __('Order Details', 'pavers'),
            [$this, 'renderMetaBox'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function renderMetaBox($post): void
    {
        $fields = $this->getFields();
        wp_nonce_field('pavers_order_meta', 'pavers_order_meta_nonce');
        ?>
        <table class="widefat fixed" style="margin-top: 12px;">
            <tbody>
            <?php foreach ($fields as $key => $label) : ?>
                <tr>
                    <th scope="row" style="width: 200px;">
                        <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
                    </th>
                    <td>
                        <?php
                        $value = get_post_meta($post->ID, $key, true);
                        if ($key === 'pavers_inscription') :
                            ?>
                            <textarea id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" rows="3" class="widefat"><?php echo esc_textarea($value); ?></textarea>
                        <?php else : ?>
                            <input id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" type="text" class="widefat" value="<?php echo esc_attr($value); ?>" />
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public function saveMeta(int $postId, $post): void
    {
        if (! isset($_POST['pavers_order_meta_nonce']) || ! wp_verify_nonce((string) $_POST['pavers_order_meta_nonce'], 'pavers_order_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        foreach ($this->getFields() as $key => $label) {
            $value = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';

            if ($key === 'pavers_inscription') {
                update_post_meta($postId, $key, sanitize_textarea_field($value));
            } elseif ($key === 'pavers_product_id') {
                update_post_meta($postId, $key, absint($value));
            } else {
                update_post_meta($postId, $key, sanitize_text_field($value));
            }
        }
    }

    public function registerColumns(array $columns): array
    {
        $custom = [
            'pavers_donor_name' => __('Donor', 'pavers'),
            'pavers_donor_email' => __('Email', 'pavers'),
            'pavers_product_id' => __('Product', 'pavers'),
            'pavers_inscription' => __('Inscription', 'pavers'),
        ];

        return array_slice($columns, 0, 2, true) + $custom + array_slice($columns, 2, null, true);
    }

    public function renderColumns(string $column, int $postId): void
    {
        switch ($column) {
            case 'pavers_donor_name':
                echo esc_html(get_post_meta($postId, 'pavers_donor_name', true));
                break;
            case 'pavers_donor_email':
                echo esc_html(get_post_meta($postId, 'pavers_donor_email', true));
                break;
            case 'pavers_inscription':
                echo esc_html(get_post_meta($postId, 'pavers_inscription', true));
                break;
            case 'pavers_product_id':
                $productId = (int) get_post_meta($postId, 'pavers_product_id', true);
                if ($productId) {
                    $title = get_the_title($productId);
                    if ($title) {
                        echo esc_html($title);
                    } else {
                        echo esc_html($productId);
                    }
                }
                break;
        }
    }

    private function getFields(): array
    {
        return [
            'pavers_donor_name' => __('Donor name', 'pavers'),
            'pavers_donor_email' => __('Donor email', 'pavers'),
            'pavers_donor_phone' => __('Phone number', 'pavers'),
            'pavers_product_id' => __('Product ID', 'pavers'),
            'pavers_inscription' => __('Inscription message', 'pavers'),
        ];
    }
}
