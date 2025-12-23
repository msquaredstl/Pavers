<?php
/**
 * Paver customization fields rendered inside the product add-to-cart form.
 *
 * @var array $data
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<div class="pavers-customizer">
    <h3 class="pavers-customizer__title"><?php esc_html_e('Customize Your Paver', 'pavers'); ?></h3>
    <p class="pavers-customizer__hint"><?php esc_html_e('Choose how the text should appear on the physical paver.', 'pavers'); ?></p>

    <?php wp_nonce_field('pavers_customize', 'pavers_customization_nonce'); ?>

    <div class="pavers-customizer__grid">
        <div class="pavers-field">
            <label for="pavers_line1"><?php esc_html_e('Line 1 *', 'pavers'); ?></label>
            <input type="text" id="pavers_line1" name="pavers_line1" value="<?php echo esc_attr($data['line1']); ?>" required>
        </div>
        <div class="pavers-field">
            <label for="pavers_line2"><?php esc_html_e('Line 2 (optional)', 'pavers'); ?></label>
            <input type="text" id="pavers_line2" name="pavers_line2" value="<?php echo esc_attr($data['line2']); ?>">
        </div>
        <div class="pavers-field">
            <label for="pavers_line3"><?php esc_html_e('Line 3 (optional)', 'pavers'); ?></label>
            <input type="text" id="pavers_line3" name="pavers_line3" value="<?php echo esc_attr($data['line3']); ?>">
        </div>
        <div class="pavers-field">
            <label for="pavers_alignment"><?php esc_html_e('Alignment', 'pavers'); ?></label>
            <select id="pavers_alignment" name="pavers_alignment">
                <option value="center" <?php selected($data['alignment'], 'center'); ?>><?php esc_html_e('Center', 'pavers'); ?></option>
                <option value="left" <?php selected($data['alignment'], 'left'); ?>><?php esc_html_e('Left', 'pavers'); ?></option>
                <option value="right" <?php selected($data['alignment'], 'right'); ?>><?php esc_html_e('Right', 'pavers'); ?></option>
            </select>
        </div>
    </div>
    <p class="pavers-customizer__note"><?php esc_html_e('Your engraving layout will be saved with the order so production can follow your preferred formatting.', 'pavers'); ?></p>
</div>
