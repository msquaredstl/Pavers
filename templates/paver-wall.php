<?php
/** @var array $orders */
?>
<div class="pavers-wall">
    <?php if (empty($orders)) : ?>
        <p><?php esc_html_e('No pavers to display yet.', 'pavers'); ?></p>
    <?php else : ?>
        <div class="pavers-wall__grid">
            <?php foreach ($orders as $order) : ?>
                <?php
                $inscription = get_post_meta($order->ID, 'pavers_inscription', true);
                $name = get_post_meta($order->ID, 'pavers_donor_name', true);
                ?>
                <div class="pavers-wall__item">
                    <div class="pavers-wall__inscription">
                        <?php echo nl2br(esc_html($inscription)); ?>
                    </div>
                    <?php if ($name) : ?>
                        <div class="pavers-wall__footer">
                            <?php echo esc_html($name); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
