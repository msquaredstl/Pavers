<?php

declare(strict_types=1);

namespace Pavers\Admin;

class SettingsPage
{
    public const OPTION_KEY = 'pavers_settings';
    private const PAGE_SLUG = 'pavers-settings';

    public function register(): void
    {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default' => self::defaults(),
        ]);
    }

    public function addMenu(): void
    {
        add_options_page(
            __('Pavers', 'pavers'),
            __('Pavers', 'pavers'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render']
        );
    }

    public function sanitize($input): array
    {
        if (! is_array($input)) {
            $input = [];
        }

        return [
            'recipient_email' => isset($input['recipient_email']) ? sanitize_email($input['recipient_email']) : '',
            'success_message' => isset($input['success_message']) ? sanitize_text_field($input['success_message']) : '',
            'error_message' => isset($input['error_message']) ? sanitize_text_field($input['error_message']) : '',
            'disclaimer' => isset($input['disclaimer']) ? wp_kses_post($input['disclaimer']) : '',
        ];
    }

    public function render(): void
    {
        $options = get_option(self::OPTION_KEY, self::defaults());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Pavers Settings', 'pavers'); ?></h1>
            <form action="options.php" method="post">
                <?php settings_fields(self::OPTION_KEY); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="pavers_recipient_email"><?php esc_html_e('Notification recipient email', 'pavers'); ?></label>
                        </th>
                        <td>
                            <input
                                type="email"
                                id="pavers_recipient_email"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[recipient_email]"
                                value="<?php echo isset($options['recipient_email']) ? esc_attr($options['recipient_email']) : ''; ?>"
                                class="regular-text"
                                required
                            />
                            <p class="description"><?php esc_html_e('Where new paver request notifications should be sent.', 'pavers'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pavers_success_message"><?php esc_html_e('Success message', 'pavers'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="pavers_success_message"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[success_message]"
                                value="<?php echo isset($options['success_message']) ? esc_attr($options['success_message']) : ''; ?>"
                                class="regular-text"
                                required
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pavers_error_message"><?php esc_html_e('Error message', 'pavers'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="pavers_error_message"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[error_message]"
                                value="<?php echo isset($options['error_message']) ? esc_attr($options['error_message']) : ''; ?>"
                                class="regular-text"
                                required
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pavers_disclaimer"><?php esc_html_e('Form disclaimer', 'pavers'); ?></label>
                        </th>
                        <td>
                            <textarea
                                id="pavers_disclaimer"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[disclaimer]"
                                rows="4"
                                class="large-text"
                            ><?php echo isset($options['disclaimer']) ? esc_textarea($options['disclaimer']) : ''; ?></textarea>
                            <p class="description"><?php esc_html_e('Shown beneath the form to remind donors about engraving requirements.', 'pavers'); ?></p>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function defaults(): array
    {
        return [
            'recipient_email' => get_option('admin_email'),
            'success_message' => __('Thank you for your submission! We will contact you soon.', 'pavers'),
            'error_message' => __('Something went wrong. Please try again later.', 'pavers'),
            'disclaimer' => __('Your message will be reviewed before engraving. Maximum of 3 lines with 15 characters each.', 'pavers'),
        ];
    }
}
