<?php
/** @var string $action */
/** @var string $success_message */
/** @var string $error_message */
/** @var string $disclaimer */
/** @var string $status */
/** @var int $product_id */
?>

<div class="pavers-form">
    <?php if ($status === 'success') : ?>
        <div class="pavers-notice pavers-notice--success">
            <?php echo esc_html($success_message); ?>
        </div>
    <?php elseif ($status === 'error') : ?>
        <div class="pavers-notice pavers-notice--error">
            <?php echo esc_html($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url($action); ?>">
        <input type="hidden" name="action" value="pavers_submit_order" />
        <input type="hidden" name="pavers_product_id" value="<?php echo esc_attr((string) $product_id); ?>" />
        <?php wp_nonce_field('pavers_submit_order'); ?>

        <div class="pavers-form__grid">
            <label class="pavers-field">
                <span class="pavers-field__label"><?php esc_html_e('Your name', 'pavers'); ?> *</span>
                <input type="text" name="pavers_name" required />
            </label>
            <label class="pavers-field">
                <span class="pavers-field__label"><?php esc_html_e('Email', 'pavers'); ?> *</span>
                <input type="email" name="pavers_email" required />
            </label>
            <label class="pavers-field">
                <span class="pavers-field__label"><?php esc_html_e('Phone number', 'pavers'); ?></span>
                <input type="tel" name="pavers_phone" />
            </label>
        </div>

        <label class="pavers-field">
            <span class="pavers-field__label"><?php esc_html_e('Engraving text', 'pavers'); ?> *</span>
            <textarea name="pavers_inscription" rows="3" maxlength="120" placeholder="<?php esc_attr_e('Up to 3 lines, 15 characters each', 'pavers'); ?>" required></textarea>
        </label>

        <?php if (! empty($disclaimer)) : ?>
            <div class="pavers-form__disclaimer">
                <?php echo wp_kses_post(wpautop($disclaimer)); ?>
            </div>
        <?php endif; ?>

        <button type="submit" class="pavers-button"><?php esc_html_e('Submit paver request', 'pavers'); ?></button>
    </form>
</div>
